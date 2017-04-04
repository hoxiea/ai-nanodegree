(* Hoxie Ackerman
   Programming Languages, Part A
   Assignment 1: Tests *)

use "hw1.sml";

(* Exercise 1: is_older *)
val ex1test1 = is_older((2014, 1, 1), (2016, 1, 1)) = true
val ex1test2 = is_older((2016, 1, 15), (2016, 2, 30)) = true
val ex1test3 = is_older((2016, 1, 1), (2016, 1, 2)) = true

val ex1test4 = is_older((2016, 1, 1), (2015, 1, 1)) = false
val ex1test5 = is_older((2016, 4, 1), (2016, 3, 1)) = false
val ex1test6 = is_older((2016, 5, 2), (2016, 5, 1)) = false
val ex1test7 = is_older((2016, 5, 2), (2016, 5, 2)) = false   (* same date *)
							  

(* Exercise 2: number_in_month *)
val dates2 = [
    (2016, 1, 15),
    (2016, 1, 20),
    (2016, 3, 4),
    (2016, 3, 15),
    (2016, 3, 1),
    (2016, 3, 25)
]
val ex2test1 = number_in_month(dates2, 1) = 2
val ex2test2 = number_in_month(dates2, 2) = 0
val ex2test3 = number_in_month(dates2, 3) = 4

(* Exercise 3: number_in_months *)
val dates3 = [
    (2016, 1, 15), (2016, 1, 20),
    (2016, 2, 11),
    (2016, 3, 4), (2016, 3, 15), (2016, 3, 1), (2016, 3, 25),
    (2016, 5, 1), (2016, 5, 15), (2016, 5, 24)
]

val ex3test1 = number_in_months(dates3, []) = 0
val ex3test2 = number_in_months(dates3, [1]) = 2
val ex3test3 = number_in_months(dates3, [1, 2]) = 3
val ex3test4 = number_in_months(dates3, [1, 3]) = 6
val ex3test5 = number_in_months(dates3, [1, 5, 3]) = 9
							 
(* Exercise 4: dates_in_month *)
val ex4winners = [(2016, 1, 15), (2016, 1, 30)]
val ex4losers = [(2016, 2, 15), (2016, 10, 30), (2016, 4, 15)]
val ex4dates = ex4winners @ ex4losers

val ex4test1 = dates_in_month (ex4dates, 1) = ex4winners
val ex4test2 = dates_in_month (ex4dates, 12) = []


(* Exercise 5: dates_in_months *)
val jandates = [(2016, 1, 15), (2016, 1, 20)]
val febdates = [(2016, 2, 11)]
val mardates = [(2016, 3, 4), (2016, 3, 15), (2016, 3, 1), (2016, 3, 25)]
val maydates = [(2016, 5, 1), (2016, 5, 15), (2016, 5, 24)]
val alldates = jandates @ febdates @ mardates @ maydates

val ex5test1 = dates_in_months(alldates, []) = []
val ex5test2 = dates_in_months(alldates, [1]) = jandates
val ex5test3 = dates_in_months(alldates, [2, 1]) = febdates @ jandates
val ex5test4 = dates_in_months(alldates, [2, 1, 5]) = febdates @ jandates @ maydates

										
(* Exercise 6: get_nth *)
val ex6strings = ["hey", "there", "i", "love", "functional", "programming"]
val ex6test1 = get_nth(ex6strings, 1) = "hey"
val ex6test2 = get_nth(ex6strings, 2) = "there"
val ex6test3 = get_nth(ex6strings, 5) = "functional"

					    
(* Exercise 7: date_to_string *)
val ex7test1 = date_to_string((2016, 2, 1)) = "February 1, 2016"
val ex7test2 = date_to_string((1987, 7, 27)) = "July 27, 1987"
val ex7test3 = date_to_string((1643, 1, 4)) = "January 4, 1643"
val ex7test4 = date_to_string((1989, 11, 9)) = "November 9, 1989"


(* Exercise 8: number_before_reaching_sum *)
val ex8test1 = number_before_reaching_sum(1, [1, 2, 3, 4]) = 0
val ex8test2 = number_before_reaching_sum(2, [1, 2, 3, 4]) = 1
val ex8test3 = number_before_reaching_sum(4, [1, 2, 3, 4]) = 2
val ex8test4 = number_before_reaching_sum(6, [1, 2, 3, 4]) = 2
val ex8test5 = number_before_reaching_sum(7, [1, 2, 3, 4]) = 3
val ex8test6 = number_before_reaching_sum(9, [1, 2, 3, 4]) = 3


(* Exercise 9: what_month *)
val ex9test1 = what_month(5) = 1
val ex9test2 = what_month(31) = 1
val ex9test3 = what_month(32) = 2
val ex9test4 = what_month(59) = 2
val ex9test5 = what_month(130) = 5
val ex9test6 = what_month(365) = 12


(* Exercise 10: month_range *)
val ex10test1 = month_range(15, 18) = [1, 1, 1, 1]
val ex10test2 = month_range(30, 34) = [1, 1, 2, 2, 2]
val ex10test3 = month_range(10, 7) = []


(* Exercise 11: oldest *)
(* Note: older means "comes before *)
val ex11test1 = oldest([]) = NONE
val ex11test2 = oldest([(1987, 7, 27)]) = SOME((1987, 7, 27))
val ex11test3 = oldest(jandates) = SOME((2016, 1, 15))
val ex11test4 = oldest(maydates) = SOME((2016, 5, 1))
val ex11test5 = oldest([(2016, 5, 3), (2014, 1, 11)]) = SOME((2014, 1, 11))

(* Exercise 12 *)
val contains_test1 = contains([], 2) = false
val contains_test2 = contains([1, 2, 3], 2) = true
val contains_test3 = contains([1, 2, 3], 3) = true
							    
val reverse_test1 = reverse([]) = []
val reverse_test2 = reverse([1]) = [1]
val reverse_test3 = reverse([1, 2, 3]) = [3, 2, 1]

val unique_test1 = unique([]) = []
val unique_test2 = unique([1]) = [1]
val unique_test3 = unique([1, 2, 3]) = [1, 2, 3]
val unique_test4 = unique([1, 2, 3, 2]) = [1, 2, 3]
val unique_test5 = unique([1, 2, 3, 2, 1, 0, 3]) = [1, 2, 3, 0]

(* Exercise 13 *)
val ex13test1 = reasonable_date((2016, 9, 14)) = true
val ex13test2 = reasonable_date((1987, 7, 27)) = true
val ex13test3 = reasonable_date((2142, 12, 31)) = true
val ex13test4 = reasonable_date((~100, 8, 10)) = false
val ex13test5 = reasonable_date((184, 15, 20)) = false
val ex13test6 = reasonable_date((2004, 2, 29)) = true
val ex13test7 = reasonable_date((2400, 2, 29)) = true
