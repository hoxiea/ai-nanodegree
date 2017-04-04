"""
This program runs a fairly straightforward DFS through the space of all
possible digit assignments, with a little bit of look-ahead.

At the heart of the search is a SearchNode, and the facts governing the search.

A SearchNode has:
- digit_possibilities: a list of sets of possible digits for each position
- next_fact_to_use: an integer that indicates the first unused fact

The SearchNode class stores a single copy of all facts guiding the search.

A SearchNode's digit_possibilities are guaranteed to satisfy all facts up until
next_fact_to_use.

A SearchNode's digit_possibilities are also guaranteed not to violate any
future rules in the following sense: if there are n 'pinned digits' in
digit_possibilities, i.e. indices that have exactly one possible digit, then
there are no future facts for which those pinned digits would create more
correct assignments than the fact actually had.

For example, we could obtain the following potential digit_possibilities by
following the first few facts and then making a digit substitution indicated by
a fact:
[{'1'},
 {'2'},
 {'3', '4', '7', '8', '9'},
 {'5', '6', '7', '8', '9'}]

But if there was a future fact ('1278', 1), then our digit_possibilities would
violate this rule, because the first two digits are pinned to '12xx' but '1278'
only has one correct according to the fact.

We search by starting with all digits possible for all indices, and then applying
rules. Each rule produces potentially many children to search. For example, the fact
that '1234' has one digit correct could cause us to:
- assign 1 to index1 and eliminate 2 from index2, 3 from index3, and 4 from index4
- assign 2 to index2 and eliminate 1 from index1, 3 from index3, and 4 from index4
- assign 3 to index3 and eliminate 1 from index1, 2 from index2, and 4 from index4
- assign 4 to index4 and eliminate 1 from index1, 2 from index2, and 3 from index4

But not all of these children are guaranteed to be produced: if 1 isn't a valid
digit_possibility for index1, for example, then that first new SearchNode won't
get created.

We can consider the facts in any order. I applied the single 0-correct fact first,
just to drop the number of possible digits to 9 for all indices. Then I processed
facts in decreasing digits-correct: 3s, then 2s, then 1s. This seemed like it would
present the most opportunities to eliminate branches using look-ahead: the
3-digits-correct facts would pin down 3 digits, so those pinned digits could violate
future rules with only 1- or 2-digits-correct.

On my 2.9GHz Core i5, this took:
- 10m40s if the facts are processed in num-digits-correct {0, 3, 2, 1}
- [aborted after an hour] if the facts are processed in num-digits-correct {0, 1, 2, 3}
"""

from copy import deepcopy
from helpers import read_data_file
from heapdict import heapdict
from itertools import combinations

def make_initial_digit_possibilities(num_digits):
    """
    Make a list of num_digits sets, each containing '0'..'9'
    """
    return [set(map(str, range(10))) for i in range(num_digits)]


class SearchNode(object):
    def __init__(self, digit_possibilities, num_facts_used, facts=None):
        self.digit_possibilities = digit_possibilities
        self.next_fact_to_use = num_facts_used
        if facts is not None:
            SearchNode.facts = facts

    @property
    def num_facts_used(self):
        return self.next_fact_to_use

    @property
    def num_pinned_down(self):
        return sum(len(d) == 1 for d in self.digit_possibilities)

    def num_correct_pinned_digits_in_seq(self, seq):
        """
        How many indices where we only have one option for a digit (i.e. a
        pinned digit) are aligned with the digit sequence seq?
        """
        num_correct = 0
        for index, digits in enumerate(self.digit_possibilities):
            if len(digits) > 1:
                continue
            pinned_digit = list(digits)[0]
            if seq[index] == pinned_digit:
                num_correct += 1
        return num_correct

    @property
    def pinned_digits_dont_violate_remaining_facts(self):
        """
        A pinned digit is a index with exactly 1 possible value, i.e. we've
        assigned a value to a given index. This method makes sure that all our
        pinned digits don't violate any of the remaining facts.

        The only real violation that we can look is the case when, given a
        rule, (number of correct pinned digits) > rule_num_correct
        """
        if self.num_pinned_down == 0:
            return True

        for rule_index in range(self.next_fact_to_use, len(SearchNode.facts)):
            rule_seq, num_correct = SearchNode.facts[rule_index]
            if self.num_correct_pinned_digits_in_seq(rule_seq) > num_correct:
                return False
        return True

    def used_all_facts(self):
        return self.next_fact_to_use == len(SearchNode.facts)

    def subs_is_compatible(self, subs):
        """
        Are all digit substitutions in subs valid choices for those indices?
        """
        for index, digit in subs:
            if digit not in self.digit_possibilities[index]:
                return False
        return True

    def make_substitution(self, subs, seq):
        """
        Return the digit possibilities that result from using the substitutions
        in subs (which should all correspond to seq), and then ruling out
        the non-substitution possibilities in seq

        > subs = ((0, '4'), )
        > seq = '415'
        > make_substitution(subs, seq)   # start with all 3-digit seqs possible
        [{'4'},
         {'0', '2', '3', '4', '5', '6', '7', '8', '9'},
         {'0', '1', '2', '3', '4', '6', '7', '8', '9'}]
        > make_substitution(((0, '9'), ), seq)
        None      # not a compatible substitution, index 0 pinned to '4'
        """
        if not self.subs_is_compatible(subs):
            return None

        new_digits = deepcopy(self.digit_possibilities)
        subs = set(subs)
        for index, digit in enumerate(seq):
            if (index, digit) in subs:
                # Guaranteed to be a valid substitution by subs_is_compatible
                new_digits[index] = set(digit)
            else:
                # So this pair (index, digit) is assumed to be wrong
                new_digits[index].discard(digit)
                if not new_digits[index]:
                    return None
        return new_digits

    def apply_next_fact(self):
        """
        Generate all new SearchNodes that can stem from applying the next fact.
        Each fact simply produces a bunch of different substitutions that could
        be made, which are all handled by make_substitution.
        """
        seq, num_correct = SearchNode.facts[self.next_fact_to_use]

        # Find all single-digit substitutions that work, based on current state
        single_subs = list(enumerate(seq))
        single_subs = [s for s in single_subs if self.subs_is_compatible((s,))]

        # Then take combinations of as many as are correct, according to the fact
        possible_replacements = list(combinations(single_subs, num_correct))

        children = []
        for full_replacement in possible_replacements:
            result = self.make_substitution(full_replacement, seq)
            if result:
                child = SearchNode(result, self.num_facts_used + 1)
                if child.pinned_digits_dont_violate_remaining_facts:
                    children.append(child)
        return children

    def is_winner(self):
        if not self.used_all_facts():
            return False
        return all(len(digits) == 1 for digits in self.digit_possibilities)

    def __repr__(self):
        """
        Inefficient, but really only used for debugging purposes
        """
        s = "num_facts_used = {}\n".format(self.num_facts_used)
        for index, possible_values in enumerate(self.digit_possibilities):
            s += "{}: {} ({})\n".format(str(index), repr(possible_values), len(possible_values))
        return s.strip()


def run_search(facts):
    num_digits = len(facts[0][0])
    init = SearchNode(make_initial_digit_possibilities(num_digits), 0, facts)
    queue = heapdict()
    queue[init] = 0
    num_steps = 0
    while queue:
        current_p = queue.popitem()[0]
        num_steps += 1
        if current_p.is_winner():
            print("Found solution after checking {} nodes".format(num_steps))
            return current_p
        next_possibilities = current_p.apply_next_fact()
        if next_possibilities:
            for child in next_possibilities:
                queue[child] = -1 * child.num_facts_used


# ACTUALLY SOLVE THE PROBLEM
real_facts = []
x = read_data_file(185)
for s in x:
    parts = s.split(';')
    seq = parts[0].strip()
    num_correct = int(parts[1][0])
    real_facts.append((seq, num_correct))
# real_facts.sort(key=lambda pair: -pair[1] if pair[1] > 0 else -5)   # 0, 3, 2, 1 (lookahead)
real_facts.sort(key=lambda pair: pair[1])   # 0, 1, 2, 3

ans = run_search(real_facts)
print(ans)
