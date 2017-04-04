
# Kelleher 2015, Chapter 5, Exercise 1

In this problem, we're going to use a nearest neighbor model to predict if it's a good day for surfing.

The data are available here: http://bit.ly/kelleher2015-ch5-ex1


```python
import numpy as np
import pandas as pd
from sklearn import neighbors

# Read in the training data
input_file = "ex1data.csv"
df = pd.read_csv(input_file)
target_colname = "GoodSurf"
X = df.drop(target_colname, axis=1)
y = df[target_colname]

# Configure the algorithm
k = 1
metric = "euclidean"

# Fit the model
clf = neighbors.KNeighborsClassifier(n_neighbors=k, metric=metric)
clf.fit(X, y)

# Create the data we'd like to make predictions for
new_data = pd.DataFrame.from_items([("A", [8, 8, 6]), ("B", [15, 2, 11]), ("C", [2, 18, 4])])
new_data.columns = list(X)

# Which training data value is closest to each new point?
distance_info = clf.kneighbors(new_data, n_neighbors=k)
print(distance_info)  # be careful, these are 0-indexed! they're 1-indexed in the book

# And what label did those closest points have?
predictions = clf.predict(new_data)
print(predictions)

```

    (array([[ 3.31662479],
           [ 2.82842712],
           [ 1.41421356]]), array([[3],
           [5],
           [2]]))
    [ True False  True]


These seem like very reasonable predictions:

* The first new data point is very similar to the fourth observation in the training data (index = 3).
* The second new data point is very similar to the last observation in the training data (index = 5).
* The third new data point is very similar to the third observation in the training data (index = 2).

And since we're only using k=1, the target values for these closest points are the predictions we'll make for our new observations.
