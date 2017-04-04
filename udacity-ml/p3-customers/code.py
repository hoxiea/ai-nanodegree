import numpy as np
import pandas as pd
import matplotlib.pyplot as plt
import pylab as pl

# Read dataset
data = pd.read_csv("wholesale-customers.csv")
print "Dataset has {} rows, {} columns".format(*data.shape)
print data.head()  # print the first 5 rows

# Various plots
data.hist(data.columns, figsize=(14,8), layout=(3,2))    # a bunch of individual histograms
_ = data.boxplot(list(data.columns.values), rot=45, grid=True, figsize=(10, 10), return_type='dict')  # boxplot

# Scatter plot matrix
from pandas.tools.plotting import scatter_matrix
_ = scatter_matrix(data, alpha=0.3, figsize=(14,14), diagonal="kde")


# Normalize the Data
from sklearn import preprocessing
data_stdzd = preprocessing.scale(data)
np.testing.assert_array_almost_equal(data_stdzd.mean(0), [0] * data_stdzd.shape[1])


#######
# PCA #
#######
# Apply PCA with the same number of dimensions as variables in the dataset
from sklearn.decomposition import PCA
pca = PCA()   # default n_components == min(n_samples, n_features)

# Print the components and the amount of variance in the data contained in each dimension
print pca.components_
print pca.explained_variance_ratio_
print np.cumsum(pca.explained_variance_ratio_)

# Project data onto first two components; plot
pca_first2 = PCA(2)
data_proj_top2 = pca_first2.fit_transform(data)
plt.figure()
plt.scatter(data_proj_top2[:,0], data_proj_top2[:,1])
plt.title("Data Projected Onto Top 2 Principal Components")
plt.show()




##############
# CLUSTERING #
##############
# Import clustering modules
from sklearn.cluster import KMeans
from sklearn.mixture import GMM, DPGMM

reduced_data = data_proj_top2

# Fit GMM
clusters = GMM(4, covariance_type='full')
clusters.fit(reduced_data)
print clusters

# Fit DPGMM
clusters2 = DPGMM(10, covariance_type='full')
clusters2.fit(reduced_data)

def plot_GMM_clusters(reduced_data, num_clusters):
    """
    Assumes that reduced_data is two-dimensional
    Assumes that clusters is either a KMeans or GMM that's been fit to reduced_data
    """
    from sklearn.mixture import GMM

    clusters = GMM(num_clusters, covariance_type='full')
    clusters.fit(reduced_data)

    # Plot the decision boundary by building a mesh grid to populate a graph.
    x_min, x_max = reduced_data[:, 0].min() - 1, reduced_data[:, 0].max() + 1
    y_min, y_max = reduced_data[:, 1].min() - 1, reduced_data[:, 1].max() + 1
    hx = (x_max-x_min)/1000.
    hy = (y_max-y_min)/1000.
    xx, yy = np.meshgrid(np.arange(x_min, x_max, hx), np.arange(y_min, y_max, hy))

    # Obtain labels for each point in mesh. Use last trained model.
    Z = clusters.predict(np.c_[xx.ravel(), yy.ravel()])

    try:
        centroids = clusters.means_
    except AttributeError:
        try:
            centroids = clusters.cluster_means_
        except:
            print "Couldn't extract centroids"
            return None

    # Put the result into a color plot
    Z = Z.reshape(xx.shape)
    plt.figure(1)
    plt.clf()
    plt.imshow(Z, interpolation='nearest',
               extent=(xx.min(), xx.max(), yy.min(), yy.max()),
               cmap=plt.cm.Paired,
               aspect='auto', origin='lower')

    plt.plot(reduced_data[:, 0], reduced_data[:, 1], 'k.', markersize=2)
    plt.scatter(centroids[:, 0], centroids[:, 1],
                marker='x', s=169, linewidths=3,
                color='w', zorder=10)
    plt.title('Clustering on the wholesale grocery dataset (PCA-reduced data)\n'
              'Centroids are marked with white cross')
    plt.xlim(x_min, x_max)
    plt.ylim(y_min, y_max)
    plt.xticks(())
    plt.yticks(())
    plt.show()

