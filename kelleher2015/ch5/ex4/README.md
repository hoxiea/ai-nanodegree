
# Kelleher 2015, Chapter 5, Exercise 4

## 4a)

In this problem, we're building a recommender system for a large (>100,000 items) online store. We have data about every item in the store, and whether or not every customer has purchased that item.

Given a customer C that we'd like to make a recommendation for, we'll presumably find other customers in our database who are similar to C, and then we'll recommend things that were commonly purchased by those other similar customers.

To measure similarity, we'll use a **similarity index**. If our three choices are Russell-Rao, Sokal-Michener, and Jaccard, I think that **Jaccard** would be the best choice because it ignores co-absences: items that neither customer has purchased. With such a large inventory, the vast majority of items would be co-absences for any two customers in our database, so by ignoring these, we'll be able to zoom in on the more informative other cases: co-presence, absence-presence, and presence-absence.

## 4b)

If the customers in our database are called A and B, and the new customer here is C, then

Jaccard(A, C) = $\frac{2}{3}$ and Jaccard(B, C) = $\frac{1}{4}$

So our system should recommend something that A has purchased but C has not. Item 498 looks like a winner.
