"""
Loading the boston dataset and examining its target (label) distribution.
"""

# Load libraries
import numpy as np
import pylab as pl
import sklearn
from scipy import stats
from sklearn import cross_validation
from sklearn import datasets
from sklearn import grid_search
from sklearn.tree import DecisionTreeRegressor
from sklearn.metrics import make_scorer


def load_data():
    '''Load the Boston dataset.'''
    boston = datasets.load_boston()
    return boston


def explore_city_data(city_data):
    '''Calculate the Boston housing statistics.'''

    # Get the labels and features from the housing data
    housing_prices = city_data.target
    housing_features = city_data.data

    # Size of data?
    num_obs = housing_features.shape[0]
    print "There are {} observations in this data set".format(num_obs)

    # Number of features?
    num_features = housing_features.shape[1]
    assert num_features == len(city_data.feature_names)
    print "There are {} features".format(num_features)

    # Minimum value?
    min_price = np.nanmin(housing_prices)

    # Maximum Value?
    max_price = np.nanmax(housing_prices)
    print "Min price: {}. Max price: {}".format(min_price, max_price)

    # Calculate mean?
    ave_price = np.mean(housing_prices)
    print "The average price is {}".format(ave_price)

    # Calculate median?
    median_price = np.median(housing_prices)
    print "The median price is {}".format(median_price)

    # Calculate standard deviation?
    sd_price = np.std(housing_prices)
    print "The SD of prices is {}".format(sd_price)


def performance_metric(label, prediction):
    '''
    Calculate and return the appropriate performance metric.
    http://scikit-learn.org/stable/modules/classes.html#sklearn-metrics-metrics
    '''
    return sklearn.metrics.mean_squared_error(label, prediction)


def split_data(city_data):
    """
    Randomly shuffle the sample set.
    Divide it into 70 percent training and 30 percent testing data.

    train_test_split handles both randomization and splitting
    """
    X, y = city_data.data, city_data.target
    X_train, X_test, y_train, y_test = cross_validation.train_test_split(X, y, train_size=0.7)
    return X_train, y_train, X_test, y_test


def model_complexity(X_train, y_train, X_test, y_test):
    '''Calculate the performance of the model as model complexity increases.'''

    # We will vary the depth of decision trees from 2 to 25
    min_depth = 2
    max_depth = 26
    depths = np.arange(min_depth, max_depth)

    train_err = np.zeros(len(depths))
    test_err = np.zeros(len(depths))

    print "Model Complexity: d={} to d={}".format(min_depth, max_depth)

    for i, d in enumerate(depths):
        regressor = DecisionTreeRegressor(max_depth=d)
        regressor.fit(X_train, y_train)
        train_err[i] = performance_metric(y_train, regressor.predict(X_train))
        test_err[i] = performance_metric(y_test, regressor.predict(X_test))

    # Plot the model complexity graph
    model_complexity_graph(depths, train_err, test_err)


def model_complexity_graph(max_depth, train_err, test_err):
    '''
    Plot training and test error as a function of the depth of the decision
    tree learn.
    '''
    pl.figure()
    pl.title('Decision Trees: Performance vs Max Depth')
    pl.plot(max_depth, test_err, lw=2, label='test error')
    pl.plot(max_depth, train_err, lw=2, label='training error')
    pl.legend()
    pl.xlabel('Max Depth')
    pl.ylabel('Error')
    pl.show()


def learning_curve(depth, X_train, y_train, X_test, y_test):
    '''
    Calculate the performance of the model after a set of training data.

    Learning curves look at how a single model performs as the size of the
    training set increases. The 'single model' is specified by DEPTH, the one
    hyperparameter of the model we're using in this exercise.

    So for a fixed DEPTH, train the model using some of the data.
    Then see how it performs on the test data, using function performance_metric.

    Finally, plot how things went for all runs.
    '''

    # We will vary the training set size so that we have 50 different sizes
    sizes = np.linspace(1, len(X_train), 50)
    train_err = np.zeros(len(sizes))
    test_err = np.zeros(len(sizes))

    print "Decision Tree with Max Depth: {}".format(depth)

    for i, s in enumerate(sizes):
        # Create and fit the decision tree regressor model
        regressor = DecisionTreeRegressor(max_depth=depth)
        regressor.fit(X_train[:s], y_train[:s])

        # Find the performance on the training and testing set
        train_err[i] = performance_metric(y_train[:s], regressor.predict(X_train[:s]))
        test_err[i] = performance_metric(y_test, regressor.predict(X_test))

    # Plot learning curve graph
    learning_curve_graph(sizes, train_err, test_err, depth)


def learning_curve_graph(sizes, train_err, test_err, depth=None):
    '''Plot training and test error as a function of the training size.'''
    pl.figure()
    title = 'Decision Trees: Performance vs Training Size'
    if depth is not None:
        title += ' (depth={})'.format(depth)
    pl.title(title)
    pl.plot(sizes, test_err, lw=2, label='test error')
    pl.plot(sizes, train_err, lw=2, label='training error')
    pl.legend()
    pl.xlabel('Training Size')
    pl.ylabel('Error')
    pl.show()


def fit_predict_model(city_data):
    '''Find and tune the optimal model. Make a prediction on housing data.'''

    X, y = city_data.data, city_data.target

    # Set up the Decision Tree Regressor
    best_depth = winning_complexity_distbn(city_data, 100, False)
    regressor = DecisionTreeRegressor(max_depth=best_depth)
    final_model = regressor.fit(X, y)

    # Fit the learner to the training data
    print "Final Model: "
    print final_model

    # Use the model to predict the output of a particular sample
    x_new = np.array([11.95, 0.00, 18.100, 0, 0.6590, 5.6090, 90.00, 1.385, 24, 680.0, 20.20, 332.09, 12.13]).reshape(1, -1)
    y_hat = final_model.predict(x_new)
    print "House: " + str(x_new)
    print "Prediction: " + str(y_hat)


def main():
    '''Analyze the Boston housing data. Evaluate and validate the
    performanance of a Decision Tree regressor on the Boston data.
    Fine tune the model to make prediction on unseen data.'''

    # Load data
    city_data = load_data()

    # Explore the data
    explore_city_data(city_data)

    # Training/Test dataset split
    X_train, y_train, X_test, y_test = split_data(city_data)

    # Learning Curve Graphs
    max_depths = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
    for max_depth in max_depths:
        learning_curve(max_depth, X_train, y_train, X_test, y_test)

    # Model Complexity Graph
    model_complexity(X_train, y_train, X_test, y_test)

    # Tune and predict Model
    fit_predict_model(city_data)


#################
# NEW FUNCTIONS #
#################
def winning_complexity_distbn(city_data, num_runs=100, plot=True):
    """
    Run num_runs cross-validated grid searches for best parameter value, make a
    histogram of results, and return the winning parameter value.
    """
    # Get the features and labels from the Boston housing data
    X, y = city_data.data, city_data.target
    depths = (1, 2, 3, 4, 5, 6, 7, 8, 9, 10)
    parameters = {'max_depth': depths}
    scorer = make_scorer(performance_metric, greater_is_better=False)
    best_params = np.zeros(num_runs)

    for i in xrange(num_runs):
        regressor = DecisionTreeRegressor()
        reg = grid_search.GridSearchCV(regressor, parameters, scoring=scorer)
        model = reg.fit(X, y)
        best_params[i] = model.best_params_['max_depth']

    if plot:
        pl.figure()
        pl.title('Distribution of Winning Max_Depth Values Across {} Runs'.format(num_runs))
        pl.hist(best_params, bins=np.arange(min(depths), max(depths)+1), align='left')
        pl.xlim([min(depths) - 1, max(depths) + 1])
        pl.xlabel('max_depth')
        pl.ylabel('Frequency')
        pl.show()

    return stats.mode(best_params).mode[0]


def target_hist(city_data):
    """
    Make a histogram of the target variable.
    """
    target = city_data.target
    pl.figure()
    pl.title('Distribution of Target Variable (MEDV)')
    pl.hist(target)
    pl.xlabel('Median Value of Owner-Occupied Homes ($1000s)')
    pl.ylabel('Frequency')
    pl.show()


if __name__ == "__main__":
    main()
