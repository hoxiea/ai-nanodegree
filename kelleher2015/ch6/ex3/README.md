
# Kelleher 2015, Chapter 6, Exercise 3

In this problem, we're using a **naive Bayes model with continuous predictor features**. We'll handle the continuous features by making assumptions about the underlying probability distributions, fitting pdfs to our data, and then using those pdf values as an indication of the relative likelihood of a given value, given a target value.

Data available here: http://bit.ly/kelleher2015-ch6-ex3


```python
import numpy as np
import pandas as pd

# Read in the training data AND the new data
input_file = "ch6ex3.csv"
df = pd.read_csv(input_file)

# Extract and process the new data (Russia)
new_data = df.tail(1)
new_data = new_data.drop("Status", 1)
df = df.drop(df.tail(1).index)

# Get the training data ready to go
target_colname = "Status"
X = df.drop(target_colname, axis=1)
y = df[target_colname]
```

## 3a) Naive Bayes Model, Gaussian Distributions

By "assuming that all descriptive features are Normally distributed," we'd be fitting 6 * 3 = 18 probability distributions, where each of the 6 features gets its own Normal distribution for each of the 3 factor levels.

As one example, we would assume that (SSIn | Status = ok) ~ N($\hat{\mu}$, $\hat{\sigma}$), where:

* $\hat{\mu}$ = mean(168, 156, 176, 256)
* $\hat{\sigma}$ = sd(168, 156, 176, 256)

If it seems weird to fit a Normal distribution to just four values, I'd agree with you. Hopefully there's strong domain knowledge suggesting that each of these values is legitimately Normally distributed.

Once we've fit all 18 Normal models, we're interested in the posterior probability of each target label, given the new data. Our target prediction will be the label with the **maximum a posteriori (MAP)** value.

Thankfully, this exact situation is provided by *sklearn*:


```python
from sklearn.naive_bayes import GaussianNB
gnb = GaussianNB()
gnb.fit(X, y)
```




    GaussianNB()



## 3b) Prediction 


```python
gnb.predict(new_data)
```




    array(['settler'], 
          dtype='<U7')



This seems like a reasonable prediction, just eyeballing the data: 

* The distribution of (SedIn | Status = solids) will make a prediction of "solids" highly unlikely
* The provided SSIn and SedIn values match the "settler" values better than the "ok" values
* Actually, the same is true for all the other variables, too
