# Import libraries
import numpy as np
import pandas as pd

# Read student data
student_data = pd.read_csv("student-data.csv")
print "Student data read successfully!"

# Basic summary statistics
n_students = student_data.shape[0]
n_features = student_data.shape[1] - 1
n_passed = sum(student_data['passed'] == 'yes')
n_failed = sum(student_data['passed'] == 'no')
grad_rate = (100.0 * n_passed) / n_students

# Extract feature (X) and target (y) columns
feature_cols = list(student_data.columns[:-1])  # all columns but last are features
target_col = student_data.columns[-1]  # last column is the target/label
print "Feature column(s):-\n{}".format(feature_cols)
print "Target column: {}".format(target_col)

X_all = student_data[feature_cols]  # feature values for all students
y_all = student_data[target_col]  # corresponding targets/labels
print "\nFeature values:-"
print X_all.head()  # print the first 5 rows

# Preprocess feature columns
def preprocess_features(X):
    outX = pd.DataFrame(index=X.index)  # output dataframe, initially empty

    # Check each column
    for col, col_data in X.iteritems():
        # If data type is non-numeric, try to replace all yes/no values with 1/0
        if col_data.dtype == object:
            col_data = col_data.replace(['yes', 'no'], [1, 0])
        # Note: This should change the data type for yes/no columns to int

        # If still non-numeric, convert to one or more dummy variables
        if col_data.dtype == object:
            col_data = pd.get_dummies(col_data, prefix=col)  # e.g. 'school' => 'school_GP', 'school_MS'

        outX = outX.join(col_data)  # collect column(s) in output dataframe

    return outX

X_all = preprocess_features(X_all)
print "Processed feature columns ({}):-\n{}".format(len(X_all.columns), list(X_all.columns))


# NORMALIZE THE DATA
# https://stackoverflow.com/questions/12525722/normalize-data-in-pandas
# https://en.wikipedia.org/wiki/Feature_scaling
# min_vector = X_all.min()
# max_vector = X_all.max()
# X_all_normalized = (X_all - min_vector) / (max_vector - min_vector)


# Split data into training and testing sets
num_all = student_data.shape[0]  # same as len(student_data)
num_train = 300  # about 75% of the data
num_test = num_all - num_train

training_indices = np.random.choice(student_data.index.values, num_train, replace=False)

X_train = X_all.ix[training_indices]
y_train = y_all.ix[training_indices]
X_test = X_all.drop(training_indices)
y_test = y_all.drop(training_indices)
print "Training set: {} samples".format(X_train.shape[0])
print "Test set: {} samples".format(X_test.shape[0])
assert len(student_data) == len(X_train) + len(X_test) == len(y_train) + len(y_test)


# HELPER FUNCTIONS: TRAINING, TESTING (F1), LEARNING CURVE
import time
from sklearn.metrics import f1_score, make_scorer
from functools import partial


def train_classifier(clf, X_train, y_train, verbose=False):
    """
    Train a generic sklearn classifier $clf using $X_train and $y_train.
    Mutates clf. Returns the amount of time spent training.
    """
    if verbose:
        print "Training {}...".format(clf.__class__.__name__)
    start = time.time()
    clf.fit(X_train, y_train)
    end = time.time()
    training_time = end - start
    if verbose:
        print "Done!\nTraining time (secs): {:.3f}".format(training_time)
    return training_time


def predict_labels(clf, features, target, verbose=False):
    """
    Use classifier $clf to predict target labels using $features, and compute the F1 score wrt $target.
    Return the F1 score and the time spent predicting labels.
    """
    if verbose:
        print "Predicting labels using {}...".format(clf.__class__.__name__)
    start = time.time()
    y_pred = clf.predict(features)
    end = time.time()
    predicting_time = end - start
    if verbose:
        print "Done!\nPrediction time (secs): {:.3f}".format(predicting_time)
    return f1_score(target.values, y_pred, pos_label='yes'), predicting_time


# Train and predict using different training set sizes
def train_predict(clf, X_train, y_train, X_test, y_test, verbose=False):
    if verbose:
        print "------------------------------------------"
        print "Training set size: {}".format(len(X_train))
    training_time = train_classifier(clf, X_train, y_train)
    training_f1, _ = predict_labels(clf, X_train, y_train)
    testing_f1, predicting_time = predict_labels(clf, X_test, y_test)
    if verbose:
        print "F1 score for training set: {}".format(training_f1)
        print "F1 score for test set: {}".format(testing_f1)
    return (training_f1, training_time, testing_f1, predicting_time)

f1_score_yes = partial(f1_score, pos_label='yes')
f1_score_yes.__name__ = "f1 score (pos_label='yes')"



# MODEL TIME!
from sklearn.tree import DecisionTreeClassifier
from sklearn.ensemble import RandomForestClassifier
from sklearn.discriminant_analysis import LinearDiscriminantAnalysis
from sklearn.neighbors import KNeighborsClassifier
from collections import defaultdict

num_repititions = 10
training_sizes = (100, 200, 300)
classifiers = {
    "DT": DecisionTreeClassifier(),
    "RF": RandomForestClassifier(),
    "kNN": KNeighborsClassifier(),
    "LDA": LinearDiscriminantAnalysis(),

}
performances = {c: {ts: defaultdict(list) for ts in training_sizes} for c in classifiers.keys()}
np.random.seed(123456)

for name, clf in classifiers.items():
    results = performances[name]
    for _ in xrange(num_repititions):
        for training_size in training_sizes:
            # Grab training_size samples from the training data
            training_indices = np.random.choice(X_train.index.values, training_size, replace=False)
            X_train_subset = X_train.ix[training_indices]
            y_train_subset = y_train.ix[training_indices]
            assert np.array_equal(X_train_subset.index, y_train_subset.index)
            training_f1, training_time, testing_f1, testing_time = train_predict(clf, X_train_subset, y_train_subset, X_test, y_test)
            results[training_size]['training_f1'].append(training_f1)
            results[training_size]['training_time'].append(training_time)
            results[training_size]['testing_f1'].append(testing_f1)
            results[training_size]['testing_time'].append(testing_time)

def print_performance_dict(p):
    for method, results in p.items():
        for training_size in sorted(results.keys()):
            print "-------------------"
            print "Training Size = {}".format(training_size)
            print "Average Training Time for {}: {:.3e}".format(method, np.mean(results[training_size]['training_time']))
            print "Average Training F1 for {}: {:.4}".format(method, np.mean(results[training_size]['training_f1']))
            print "Average Testing (Prediction) Time for {}: {:.3e}".format(method, np.mean(results[training_size]['testing_time']))
            print "Average Testing F1 for {}: {:.4}".format(method, np.mean(results[training_size]['testing_f1']))
            print

print_performance_dict(performances)


# FINAL MODEL: LDA gridsearch
from sklearn.grid_search import GridSearchCV
from sklearn.externals.six import StringIO
import os

dt_params = [
    {
        'criterion': ['gini', 'entropy'],
        'max_depth': range(1, 21),
        'min_samples_split': range(1, 3),
        'min_samples_leaf': range(1, 3)
    }
]

make_plots = False

# Use n=300 observations used by previous models
dt_clf = GridSearchCV(DecisionTreeClassifier(), dt_params, cv=5, scoring=make_scorer(f1_score_yes))
train_predict(dt_clf, X_train, y_train, X_test, y_test, True)
print dt_clf.best_params_
print dt_clf.best_score_
dt_final = dt_clf.best_estimator_

if make_plots:
    with open("dt1.dot", 'w') as f:
        f = tree.export_graphviz(dt_final, out_file=f)
    !dot -Tpdf dt1.dot -o dt1.pdf
    os.unlink('dt1.dot')


# Use all available data (n=395)
dt_clf2 = GridSearchCV(DecisionTreeClassifier(), dt_params, cv=5, scoring=make_scorer(f1_score_yes))
train_classifier(dt_clf2, X_all, y_all)
print dt_clf2.best_params_
print dt_clf2.best_score_
dt_final2 = dt_clf2.best_estimator_

if make_plots:
    with open("dt2.dot", 'w') as f:
        f = tree.export_graphviz(dt_final2, out_file=f)
    !dot -Tpdf dt2.dot -o dt2.pdf
    os.unlink('dt2.dot')

# Which were the important variables?
nonzero_indices = [i for i, score in enumerate(dt_final2.feature_importances_) if score > 0]
print X_all.columns[nonzero_indices]










# Tuning Final Model
dt_params = [{'criterion': ['gini', 'entropy'], 'max_depth': range(1, 21), 'min_samples_split': range(1, 10)}]
rf_params = [{'criterion': ['gini', 'entropy'], 'max_depth': range(1, 11), 'min_samples_split': range(1, 10)}]
svm_params = [
    {
        'kernel': ['rbf', 'sigmoid'],
        'C': [2 ** k for k in xrange(-15, 16, 2)],
        'gamma': [2 ** k for k in xrange(-15, 16, 2)]
    }
]

lda_params = [
    {
        'solver': ['lsqr'],
        'shrinkage': [None, 'auto'] + [x / 20.0 for x in range(1, 21)]  # 0.05, 0.1, ...
    }
]
print dt_clf.best_params_
print dt_clf.best_score_
dt_clf.grid_scores_
dt_final = dt_clf.best_estimator_
