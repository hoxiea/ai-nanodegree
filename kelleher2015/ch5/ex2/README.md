
# Kelleher 2016, Chapter 5, Exercise 2

In this exercise, we're going to use a bag-of-words model with KNN to classify emails as spam/ham.

The data are available here: http://bit.ly/kelleher2015-ch5-ex2


```python
import numpy as np
import pandas as pd
from sklearn import neighbors

# Read in the training data
input_file = "ex2data.csv"
df = pd.read_csv(input_file)
target_colname = "Spam"
X = df.drop(target_colname, axis=1)
y = df[target_colname]
```

## 2a) k=1, Euclidean distance


```python
# Configure the algorithm
k = 1
metric = "euclidean"

# Fit the model
clf_2a = neighbors.KNeighborsClassifier(n_neighbors=k, metric=metric)
clf_2a.fit(X, y)

# Create the data we'd like to make predictions for
def email_to_keyword_counts(X, email):
    keywords = [s.lower() for s in list(X)]
    email_words = email.lower().split(" ")
    new_row = [(s.title(), [1 if s in email_words else 0]) for s in keywords]
    return new_row

email = "machine learning for free"
new_data = pd.DataFrame.from_items(email_to_keyword_counts(X, email))
new_data.columns = list(X)

# What's the target level of the point nearest to our input email?
clf_2a.predict(new_data)
```




    array([False], dtype=bool)



These seem like very reasonable predictions:

* The first new data point is very similar to the fourth observation in the training data (index = 3).
* The second new data point is very similar to the last observation in the training data (index = 5).
* The third new data point is very similar to the third observation in the training data (index = 2).

And since we're only using k=1, the target values for these closest points are the predictions we'll make for our new observations.

A prediction of "False" makes sense for this email - "Machine" and "Learning" appeared only in ham messages, and this new email is extremely close to our last training data example.

## 2b) k = 3, Euclidean distance


```python
# Configure the algorithm
k = 3
metric = "euclidean"

# Fit the model
clf_2b = neighbors.KNeighborsClassifier(n_neighbors=k, metric=metric)
clf_2b.fit(X, y)
clf_2b.predict(new_data)
```




    array([ True], dtype=bool)



Apparently, the next-closest two observations were both spam, so now the majority vote concludes "Spam".

## 2c) k = 5, Euclidean distance, $w_i = \frac{1}{d_i^2}$


```python
# Configure the algorithm
k = 5
metric = "euclidean"
weights="distance"

# Fit the model
clf_2c = neighbors.KNeighborsClassifier(n_neighbors=k, metric=metric, weights=weights)
clf_2c.fit(X, y)
clf_2c.predict(new_data)
```




    array([False], dtype=bool)



The interesting thing to note here is that **$k$ is equal to the total number of training observations we have**, so we're letting all the data we have vote on the classification, where their votes are weighted based on how far away from the new observation they are.

## 2d) k = 3, Manhattan distance


```python
# Configure the algorithm
k = 3
metric = "manhattan"

# Fit the model
clf_2d = neighbors.KNeighborsClassifier(n_neighbors=k, metric=metric)
clf_2d.fit(X, y)
clf_2d.predict(new_data)
```




    array([False], dtype=bool)



## 2e) k=3, Cosine Similarity


```python
from sklearn.metrics.pairwise import cosine_similarity
cosine_similarity(X, new_data)
```




    array([[ 0.        ],
           [ 0.53033009],
           [ 0.28867513],
           [ 0.4330127 ],
           [ 0.8660254 ]])



So observations 2, 4, and 5 are most similar in terms of cosines. The majority of these have label "Ham," so that's the prediction we make with $k=3$.
