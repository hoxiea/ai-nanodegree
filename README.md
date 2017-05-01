# Hello, Udacity!

And thanks for checking out my AI Nanodegree repo.

You want to quickly determine if I'm a strong enough programmer to complete the nanodegree and become an awesome Udacity success story, and I believe that I am.

Here are some of my programming highlights.

## Project Euler in Python3 ([code](/euler-python3/))
I've solved 122 Project Euler problems in **Python3**, putting me in the Top 1% of solvers:

![Project Euler badge: 122 solved](https://projecteuler.net/profile/hoxiea2.png "Project Euler badge")

In the spirit of the website, I won't post all of my solutions, but I'll highlight a few of my favorites:

* [Problem 185](https://projecteuler.net/problem=185) (2256 solvers): My solution uses DFS with lookahead to efficiently search the space of possibilities. A nice example of the various common data structures and comprehensions in Python, object oriented programming, anonymous functions, and functional programming constructs.

* [Problem 286](https://projecteuler.net/problem=286) (1566 solvers): I don't often encounter new probability distributions, but the Poisson Binomial distribution was a new one. Once I found that, it was fairly simple to implement the discrete Fourier transform that lets you calculate pmf values with the high numerical precision that's requested.

* [Problem 345](https://projecteuler.net/problem=345) (3484 solvers): My solution uses an object-oriented approach (including simple class inheritance), along with the NetworkX library, to construct a weighted bipartite graph that quickly reveals the correct answer.

## Web App for Optimal Pharmacy Dispensing ([code](/dosis/))

I wrote and maintain a suite of automation web apps for a local long-term-care pharmacy. The website (hosted on Heroku, used daily) uses **PHP, HTML5, CSS3, and Javascript**. This project was a good exercise in working with a client who wasn't 100% sure what they wanted at the onset. (**Agile**, anyone?)

I've included the most technically complex program in the suite: the Dosis Optimizer. Dosis machines are machines that automatically package and label prescription drugs. A Dosis machine can only hold a couple hundred drugs, and the pharmacy must choose which drugs to put into the machine. My client wanted to make their Dosis stocking choices based on usage data: if the most popular drugs are in the machine, then the machine will be doing as much work as possible.

This program takes as input:

* an exported list of all the current drugs in the Dosis machine
* usage data (at the individual prescription level) from the past 1-3 months
* many possible configuration options

It then uses **7 classes and about 1200 lines of helper functions** to:

* Merge 50k+ lines of usage information into the approximately 1000 Drugs they represent based on an unambiguous identification code
* Combine Drugs with different identification codes that are probably the same compound (generics, different manufacturers, etc.), based on **natural language processing and regular expressions**
* Match and disambiguate Dosis Drugs against Usage Drugs
* Filter out drugs using **regular expressions**
* Identify which Drugs in the machine should be removed, and which Drugs should replace them
* Output results in a color-coded, easily understood webpage

My client is one location in a chain of approximately 15 pharmacies across the country, and their Dosis efficiency numbers have been the best for three years running.

## Udacity Machine Learning Nanodegree ([code](/udacity-ml/))
I started the Machine Learning nanodegree in late 2015 before getting sidetracked by the thriving math/CS tutoring business I founded. Feedback from reviewers on the assignments I submitted was overwhelmingly positive.

## ML Textbook (Kelleher et al., 2015) ([code](/kelleher2015/))

I was recently working through a machine learning textbook, [*Fundamentals of Machine Learning for Predictive Data Analytics*](http://a.co/ciP6lih). I used **R/Rmarkdown** for Chapter 4 (Information-based Learning), and then **Python3 (with jupyter notebook and iPython)** for Chapters 5 (Similarity-based Learning), 6 (Probability-based Learning), and 7 (Error-based Learning).

## The Little Schemer ([code](/little-schemer/))

If there was ever a time to advertise the fact that I once worked through a cool **Lisp/Scheme** book, the application for the AI nanodegree would be it.

I spent a year between BU and Berkeley working as a statistical geneticist (essentially an **R programmer**) in Cambridge, MA. I enjoyed programming so much that I seriously considered becoming a software engineer. I found Steve Yegge's [blog posts](https://steve-yegge.blogspot.com/) to be highly entertaining and quite informative; one of his top [book suggestions](https://sites.google.com/site/steveyegge2/ten-great-books) was [The Little Schemer](http://a.co/alN1X7Q).

I loved the way that The Little Schemer helped me to think about recursion. Weird, great little book.

## Coursera: Programming Languages ([code](/prog-lang-part-a/))

One of my favorite MOOCs I've taken is **Programming Languages**, taught by University of Washington's [Dan Grossman](https://homes.cs.washington.edu/~djg/) via Coursera. I took the original iteration in 2012, which featured sections in **Standard ML, Ruby, and Racket**. In the fall of 2016, I went back and did Part A (**Standard ML**) again - I had grown significantly as a programmer, and after a few years of exposure to **Lisp and Scala**, I was curious to work with ML again. Just as much fun as it was the first time around. 99.7% average for Part A.

## Thank you for considering my application!
