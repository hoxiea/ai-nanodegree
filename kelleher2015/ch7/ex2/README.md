
# Kelleher 2015, Chapter 7, Exercise 2

In this problem, we're going to explore using gradient descent to fit a multiple linear regression.


```python
import numpy as np
import pandas as pd

# Read in the training data AND the new data
input_file = "ex2data.csv"
df = pd.read_csv(input_file)

# Separate into features and target
target_colname = "OxyCon"
X = df.drop(target_colname, axis=1)
y = df[target_colname]

# Add a 1, for intercept weight purposes
X["Int"] = 1
X = X[["Int", "Age", "HR"]]
```

## 1a) Prediction, Given Weights

Make a prediction for each observation, given some weights:


```python
weights = np.array([-59.50, -0.15, 0.60])
yhat = np.dot(X, weights)
yhat
```




    array([ 17.15,  26.  ,  25.55,  13.4 ,   8.9 ,  20.9 ,  28.85,  19.4 ,
            17.75,  29.6 ,  19.85,  16.85])



## 1b) Sum of Squared Errors


```python
np.sum((yhat - y) ** 2)
```




    4035.1863000000026



## 1c) Gradient Descrent Iteration


```python
alpha = 0.000002
weights2 = np.copy(weights)
for j in range(2):
    weights2[j] = weights2[j] + alpha * np.sum((y - yhat) * X.iloc[:, j])
weights2
```




    array([-59.49956706,  -0.13174326,   0.6       ])



## 1d) Sum of Squared Errors, New Weights


```python
yhat2 = np.dot(X, weights2)
np.sum((yhat2 - y) ** 2)
```




    3708.913137307014



Hooray, our SSE has decreased after an iteration of gradient descrent.
