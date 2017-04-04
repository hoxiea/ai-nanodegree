### Initialize stuff
city_data = load_data()
X, y = city_data.data, city_data.target
X_train, X_test, y_train, y_test = cross_validation.train_test_split(X, y, test_size=0.2)

### Section 3: Analyzing Model Performance ###
# Learning curves
max_depths = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
for max_depth in max_depths:
    learning_curve(max_depth, X_train, y_train, X_test, y_test)

model_complexity(*split_data(city_data))
learning_curve(3, X_train, y_train, X_test, y_test)


# Section 4: prediction
parameters = {'max_depth': (1, 2, 3, 4, 5, 6, 7, 8, 9, 10)}
scorer = make_scorer(performance_metric, greater_is_better=False)

regressor = DecisionTreeRegressor()
reg = grid_search.GridSearchCV(regressor, parameters,scoring=scorer)
final_model = reg.fit(X, y)   # try all parameter combinations, grab the best one
print "Winning cross-validated parameter val: max_depth={}".format(final_model.best_params_['max_depth'])

x_new = np.array([11.95, 0.00, 18.100, 0, 0.6590, 5.6090, 90.00, 1.385, 24, 680.0, 20.20, 332.09, 12.13]).reshape(1, -1)
y_pred = reg.predict(x)
print "House: " + str(x_new)
print "Prediction: " + str(y_pred)
