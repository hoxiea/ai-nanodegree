
# Kelleher 2015, Chapter 7, Exercise 3

In this problem, we're given the weights for a multivariate logistic regression, and we're asked to make predictions for new observations.


```python
import numpy as np
import pandas as pd

weights = np.array([-3.82398, -0.02990, -0.09089, -0.19558, 0.02999, 0.74572])

# Read in the new observations
input_file = "ex3data.csv"
X = pd.read_csv(input_file)

# Add a 1, for intercept weight purposes
X["Int"] = 1
X = X[["Int"] + X.columns[:-1].tolist()]
X
```




<div>
<table border="1" class="dataframe">
  <thead>
    <tr style="text-align: right;">
      <th></th>
      <th>Int</th>
      <th>Age</th>
      <th>Economic</th>
      <th>ShopFreq</th>
      <th>ShopValue</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <th>0</th>
      <td>1</td>
      <td>56</td>
      <td>b</td>
      <td>1.60</td>
      <td>109.32</td>
    </tr>
    <tr>
      <th>1</th>
      <td>1</td>
      <td>21</td>
      <td>c</td>
      <td>4.92</td>
      <td>11.28</td>
    </tr>
    <tr>
      <th>2</th>
      <td>1</td>
      <td>48</td>
      <td>b</td>
      <td>1.21</td>
      <td>161.19</td>
    </tr>
    <tr>
      <th>3</th>
      <td>1</td>
      <td>37</td>
      <td>c</td>
      <td>0.72</td>
      <td>170.65</td>
    </tr>
    <tr>
      <th>4</th>
      <td>1</td>
      <td>32</td>
      <td>a</td>
      <td>1.08</td>
      <td>165.39</td>
    </tr>
  </tbody>
</table>
</div>



Logistic regression handles continues features just fine. 

To deal with the categorical (probably ordinal) variable Economic (which captures the socioeconomic band to which the customer belongs), we're apparently going to use indicators for levels 'b' and 'c', where zeros for both indicates the customer is at level 'a'. Thankfully, this is easy to do in pandas:


```python
X = pd.get_dummies(X, drop_first=True)
```


```python
# Reorder the columns, so that they line up with the order of the weights
# Also note that in the weights provided, ShopVal precedes ShopFreq, but in the data, ShopFreq comes first. Flip em'
X = X[X.columns[:2].tolist() + X.columns[-2:].tolist() + X.columns[range(3,1,-1)].tolist()]
```

Finally, we're ready to apply the logistic regression model to our data...


```python
from scipy.special import expit as logistic
logistic(np.dot(X, weights))
```




    array([ 0.24645465,  0.34519446,  0.59540115,  0.6292153 ,  0.72802866])



The actual predictions made by this model will depend on where we draw the line between "don't give a free gift" and "give a free gift," but if the line is at 0.5, then the last thee customers in our dataset will receive the gift and the first two won't.
