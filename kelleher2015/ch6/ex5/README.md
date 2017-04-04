
# Kelleher 2015, Chapter 6, Exercise 5

In this exercise, we're going to predict the preferred communication channel of policy holders at an insurance company, based on information about them.

Data are available here: 


```python
import numpy as np
import pandas as pd

# Read in the training data
input_file = "ch6ex5.csv"
df = pd.read_csv(input_file)

# Get the training data ready to go
target_colname = "PrefChannel"
X = df.drop(target_colname, axis=1)
y = df[target_colname]
```

## 5a) Equal-Frequency Binning for Age

We saw in the chapter that there are multiple options for Naive Bayes models to handle continuous variables. In Exercise 3, we explored one of those options: we assumed Normality for each conditional distribution, and estimated the mean and standard deviation from the (limited) data we had. 

In this exercise, we'll take a different approach and use **equal-frequency binning** to convert the quantitative variable Age into a categorical variable. With 9 observations and 3 requested levels (young, middle-aged, mature), the youngest three policy holders will be "young", the next 3 will be "middle-aged", and the oldest three will be "mature". (Note that each bin has the same number of observations in it - thus, "equal-frequency.")

This wouldn't be hard to program manually, but pandas has a function to do this for us:


```python
X.Age = pd.qcut(X.Age, 3, labels=["young", "middle-aged", "mature"])
```

## 5b) Excluding Features

**The obvious feature to exclude is "Occupation."** Every person in our training data has a different occupation, so knowing a person's occupation tells us nothing about his/her preferred communication channel. (Plus, dropping Occupation will mean far fewer probabilities to estimate, not that we're going to run into computational issues with a dataset this small.)

Gender can stay - 75% of females prefer phone, whereas only 60% of men prefer phone, so this seems possibly informative.

Age actually doesn't seem very informative: in all three of our categorical buckets, there are 2 of one label and 1 of the other. But it can stay for now.

PolicyType has potential to be informative - 75% of TypeC's prefer phone, only 33% of TypeA's prefer phone, and 50% of TypeB's prefer phone.


```python
X = X.drop("Occupation", 1)
```

## 5c) Calculating Probabilities for Naive Bayes

Excluding Occupation and using equal-frequency binning for Age, we have the following probabilities:

* P(email) = 4/9
* P(phone) = 5/9
* P(female | email) = 1/4   =>   P(male | email) = 3/4
* P(female | phone) = 3/5   =>   P(male | phone) = 2/5

And so on.

## 5d) Predicting

Unfortunately, sklearn doesn't directly handle non-binary categorical features for Naive Bayes. It *does*, however, support Bernoulli Naive Bayes.

I did a little reading, and it seems like the strategy is to encode the various factor levels as indicators, and then use those with the BernoulliNB.

https://stackoverflow.com/questions/38621053/how-can-i-use-sklearn-naive-bayes-with-multiple-categorical-features
https://datascience.stackexchange.com/questions/9854/sklearn-naive-bayes-vs-categorical-variables

pandas.get_dummies to the rescue! Note


```python
X_dropfirst = pd.get_dummies(X, drop_first=True)
X_dropfirst
```




<div>
<table border="1" class="dataframe">
  <thead>
    <tr style="text-align: right;">
      <th></th>
      <th>Gender_male</th>
      <th>Age_middle-aged</th>
      <th>Age_mature</th>
      <th>PolicyType_B</th>
      <th>PolicyType_C</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <th>0</th>
      <td>0</td>
      <td>1</td>
      <td>0</td>
      <td>0</td>
      <td>1</td>
    </tr>
    <tr>
      <th>1</th>
      <td>0</td>
      <td>0</td>
      <td>1</td>
      <td>0</td>
      <td>0</td>
    </tr>
    <tr>
      <th>2</th>
      <td>1</td>
      <td>0</td>
      <td>0</td>
      <td>0</td>
      <td>0</td>
    </tr>
    <tr>
      <th>3</th>
      <td>0</td>
      <td>1</td>
      <td>0</td>
      <td>1</td>
      <td>0</td>
    </tr>
    <tr>
      <th>4</th>
      <td>1</td>
      <td>0</td>
      <td>1</td>
      <td>0</td>
      <td>1</td>
    </tr>
    <tr>
      <th>5</th>
      <td>1</td>
      <td>0</td>
      <td>0</td>
      <td>0</td>
      <td>0</td>
    </tr>
    <tr>
      <th>6</th>
      <td>1</td>
      <td>1</td>
      <td>0</td>
      <td>0</td>
      <td>1</td>
    </tr>
    <tr>
      <th>7</th>
      <td>1</td>
      <td>0</td>
      <td>1</td>
      <td>1</td>
      <td>0</td>
    </tr>
    <tr>
      <th>8</th>
      <td>0</td>
      <td>0</td>
      <td>0</td>
      <td>0</td>
      <td>1</td>
    </tr>
  </tbody>
</table>
</div>



We need to convert our query into this new encoding scheme...

* female => Gender_male = 0
* (Age = 30) => Age = young, since the ages at the edges of young and middle-aged are 21 and 43 (with 32 at the middle, thus an age of 30 falls into the 'young' category)  =>  0 for middle-aged and old
* (Policy = A) => 0 for Types B and C


```python
new_data_binary = pd.DataFrame(columns=list(X_dropfirst))
new_data_binary.loc[0] = np.array([0,0,0,0,0])
new_data_binary
```




<div>
<table border="1" class="dataframe">
  <thead>
    <tr style="text-align: right;">
      <th></th>
      <th>Gender_male</th>
      <th>Age_middle-aged</th>
      <th>Age_mature</th>
      <th>PolicyType_B</th>
      <th>PolicyType_C</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <th>0</th>
      <td>0.0</td>
      <td>0.0</td>
      <td>0.0</td>
      <td>0.0</td>
      <td>0.0</td>
    </tr>
  </tbody>
</table>
</div>




```python
from sklearn.naive_bayes import BernoulliNB
clf = BernoulliNB(alpha=0)
clf.fit(X_dropfirst, y)
clf.predict(new_data_binary)
```




    array(['phone'], 
          dtype='<U5')



Thus, we predict that this individual will prefer to be contacted via phone.


```python
clf.get_params()
```




    {'alpha': 1.0, 'binarize': 0.0, 'class_prior': None, 'fit_prior': True}




```python

```
