from helpers import memoize, list_product
from scipy import optimize
from sympy import exp, I, pi, N

"""
Barbara is a mathematician and a basketball player.
She has found that the probability of scoring a point when shooting
from a distance x is exactly (1 - x/q),
where q is a real constant greater than 50.

During each practice run, she takes shots
from distances x = 1, x = 2, ..., x = 50 and, according to her records,
she has precisely a 2% chance to score a total of exactly 20 points.

Find q and give your answer rounded to 10 decimal places.

--------

https://en.wikipedia.org/wiki/Poisson_binomial_distribution
"""

@memoize
def p(j, q):
    """
    Probability of success on trial j, where q > 50 (apparently)
    """
    return 1 - (j / q)

def prob_k_wins(k, n, q):
    """
    What's the probability of seeing exactly k successes in n trials,
    where the success probability changes from trial to trial?

    Let p_i be the success probability for the ith trial.
    Then p_i = p(i, q), where q is unknown.

    See Wikipedia article, "discrete Fourier transform" section.
    This is essentially a direct implementation of that equation.
    """
    C = exp(2 * I * pi / (n + 1))
    result = 0
    for l in range(n+1):
        mult = C ** (-l * k)
        prod = list_product(1 + p(m, q) * ((C ** l) - 1) for m in range(1, n+1))
        result += mult * prod
    return result / (n + 1)

def f(q):
    """
    Want to find the value of q that minimizes the difference between
    the probability of 20 wins in 50 trials (a function of q)
    and 0.02 i.e. 2%
    """
    return N(prob_k_wins(20, 50, q)).as_real_imag()[0] - 0.02

ans = optimize.brentq(f, 52, 53, xtol=1e-15)
print(round(ans, 10))
