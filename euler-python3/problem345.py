from helpers import read_data_file
import networkx as nx

"""
We define the Matrix Sum of a matrix as the maximum sum of matrix elements with each element being the only one in his row and column. For example, the Matrix Sum of the matrix below equals 3315 ( = 863 + 383 + 343 + 959 + 767):

  7  53 183 439 863
497 383 563  79 973
287  63 343 169 583
627 343 773 959 943
767 473 103 699 303

Find the Matrix Sum of the big matrix contained in data file 345.

---

This can be posed as a form of the Assignment Problem (https://en.wikipedia.org/wiki/Assignment_problem),
and solved using a maximum weighted bipartite matching
(https://en.wikipedia.org/wiki/Matching_(graph_theory)#In_weighted_bipartite_graphs).

In particular, we create a Row for each row in the matrix, and a Col for each column in the matrix.
We then create a weighted bipartite graph, where:
- There is one Node for each Row, and one Node for each Col
- There's an edge between every Row Node and every Col Node, where the edge weight is the value of the "overlapping value"

Example:
1 2 3
4 5 6

There would 5 Nodes total: 2 Rows and 3 Cols.
The edge between the first Row and the first Col would have a weight of 1, the overlapping value
The edge between the second Row and the third Col would have a weight of 6, the overlapping value

The maximum weight matching of this resulting graph provides the maximizing entries of interest.
"""

data = read_data_file(345)
data = [line.strip().split(' ') for line in data]
data = [[int(element) for element in line if element] for line in data]

class Node(object):
    def __init__(self, data, index):
        self.data = data
        self.index = index

class Row(Node):
    def __repr__(self):
        return "row" + str(self.index)


class Col(Node):
    def __repr__(self):
        return "col" + str(self.index)

def get_row_col_tuples(data):
    rows = {i: tuple(data[i]) for i in range(len(data))}
    cols = {}
    for col in range(len(data[0])):
        current_col = []
        for row in range(len(data)):
            current_col.append(data[row][col])
        cols[col] = tuple(current_col)
    return rows, cols

def get_row_col_objects(data):
    rows, cols = get_row_col_tuples(data)
    row_objects = [Row(row, ri) for ri, row in rows.items()]
    col_objects = [Col(col, ci) for ci, col in cols.items()]
    return row_objects, col_objects

def build_bitartite_graph_rows_cols(data):
    """
    Build a bitartite graph with bipartite node sets (A,B), where:
    - the nodes in A are the rows of data
    - the nodes in B are the cols of data
    - the edge between A_i and B_j is data[i][j]
    """
    G = nx.Graph()
    rows, cols = get_row_col_objects(data)
    for ri, row in enumerate(rows):
        for ci, col in enumerate(cols):
            G.add_edge(row, col, weight=data[ri][ci])
    assert G.number_of_nodes() == len(rows) + len(cols)
    assert G.number_of_edges() == len(rows) * len(cols)
    return G

def ans(data):
    G = build_bitartite_graph_rows_cols(data)
    ans = nx.max_weight_matching(G)

    current_total = 0
    for node1, node2 in ans.items():
        if type(node1) is Row:
            ri, ci = node1.index, node2.index
            current_total += data[ri][ci]
    return current_total

# Test data
m1 = [
    [3, 7, 4],
    [8, 1, 5],
    [6, 2, 9]
]
assert ans(m1) == 9 + 8 + 7

# Answer
print(ans(data))
