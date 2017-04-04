
# Kelleher 2015, Chapter 5, Exercise 3

In this exercise, we're predicting the level of corruption (**continuous variable**) in a country based on macroeconomic and social features.

The data are available here: http://bit.ly/kelleher2015-ch5-ex3


```python
import numpy as np
import pandas as pd
from sklearn import neighbors

# Read in the training data AND the new data
input_file = "ch5ex3.csv"
df = pd.read_csv(input_file)

# Extract and process the new data (Russia)
new_data = df.tail(1)
new_data = new_data.drop("CPI", 1)
new_data = new_data.rename(new_data.Country)
new_data = new_data.drop("Country", 1)
df = df.drop(df.tail(1).index)

# Get the training data ready to go
target_colname = "CPI"
X = df.drop(target_colname, axis=1)
y = df[target_colname]

X = X.rename(X.Country)
X = X.drop("Country", axis=1)
```

## 3a) k=3, Euclidean Distance


```python
# Configure the algorithm
k = 3
metric = "euclidean"

# Fit the model
clf_3a = neighbors.KNeighborsRegressor(n_neighbors=k, metric=metric)
clf_3a.fit(X, y)

# What's the predicted CPI for Russia?
clf_3a.predict(new_data)
```




    array([ 4.58913333])



So we predict a CPI of approximately 4.5891 for Russia using the average CPIs of the $k=3$ nearest neighbors.

## 3b) k = 16, Euclidean distance, $w_i = \frac{1}{d_i^2}$


```python
# Define the custom weight function: SQUARED euclidean distance
inverse_squared_distance = np.vectorize(lambda d: 1.0 / (d*d))

# Configure the algorithm
k = 16
metric = "euclidean"

# Fit the model
clf_3b = neighbors.KNeighborsRegressor(n_neighbors=k, metric=metric, weights=inverse_squared_distance)
clf_3b.fit(X, y)
clf_3b.predict(new_data)
```




    array([ 5.90870754])



The weighted kNN prediction moder predicts a CPI of approximately 5.9087 for Russia.

## 3c) k = 3, Euclidean distance, Normalized data

As we learned in the section, when you're doing distance-based work, the scale of the various variables is extremely important. A variable with a naturally larger scale can dominate a Euclidean distance calculation, for example - the example on page 205 does (in my opinion) a great job of illustrating this.

To get all of our variables on the same scale, we can normalize them using **range normalization**. After deciding the range of values we want each variable to span (here we'll use low=0, high=1), we normalize as follows:

$$a_i' = low + \frac{a_i - \min(a)}{\max(a) - \min(a)} \times (high - low)$$


```python
# Normalize the data
from sklearn.preprocessing import MinMaxScaler
mm_scaler = MinMaxScaler()
X_scaled = pd.DataFrame(mm_scaler.fit_transform(X))
X_scaled.columns = list(X)

# Normalize the new data using the fitted parameters
new_data_scaled = mm_scaler.transform(new_data)

# Configure the algorithm
k = 3
metric = "euclidean"

# Fit the model
clf_3c = neighbors.KNeighborsRegressor(n_neighbors=k, metric=metric)
clf_3c.fit(X_scaled, y)
clf_3c.predict(new_data_scaled)
```




    array([ 5.96896667])



After normalizing each variable to range from 0 to 1 (thus negating the effects of different natural variable scales), the new predicted $k = 3$ CPI for Russia is approximately 5.9690.

## 3d) k = 16, Euclidean distance, $w_i = \frac{1}{d_i^2}$, normalized data


```python
# Configure the algorithm
k = 16
metric = "euclidean"

# Fit the model
clf_3d = neighbors.KNeighborsRegressor(n_neighbors=k, metric=metric, weights=inverse_squared_distance)
clf_3d.fit(X_scaled, y)
clf_3d.predict(new_data_scaled)
```




    array([ 6.6346612])



So using all of our training data with inverse squared distances for weights, we obtain a predicted CPI of approximately 6.6347 for Russia.

## 2e)

I'm surprised to learn that the actual 2011 CPI for Russia was 2.4488. The best prediction was by far for the $k=3$ model we fit on our unnormalized data in (3a). I suspect that this prediction was best because Russia happened to be near (in an unnormalized sense) to some lower-valued CPI nations... let's take a look.


```python
dists, indices = clf_3a.kneighbors(new_data, n_neighbors=3)
print(X.iloc[indices[0]])
y[indices[0]]
```

               LifeExp  Top10Income  InfantMort  MilSpend  SchoolYears
    Argentina    75.77        32.30        13.3      0.76         10.1
    China        74.87        29.98        13.7      1.95          6.4
    USA          78.51        29.85         6.3      4.72         13.7





    4    2.9961
    5    3.6356
    8    7.1357
    Name: CPI, dtype: float64



It's not clear how these data are collected, either. It says that CPI is "the perceived levels of corruption in the public sector of countries, where 0 is 'highly corrupt' and 100 is 'very clean'." If it's only people with that country whose opinions are collected, then there could be pressure to respond a certain way. And if people from 
