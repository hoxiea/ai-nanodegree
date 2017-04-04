(* Homework3 Simple Test*)
(* These are basic test cases. Passing these tests does not guarantee that your code will pass the actual homework grader *)
(* To run the test, add a new line to the top of this file: use "homeworkname.sml"; *)
(* All the tests should evaluate to true. For example, the REPL should say: val test1 = true : bool *)

use "hw3.sml";

val ex1test1 = only_capitals [] = []
val ex1test2 = only_capitals ["A","B","C"] = ["A","B","C"]
val ex1test3 = only_capitals ["Apple","banana","Car"] = ["Apple","Car"]

val ex2test1 = longest_string1 [] = ""
val ex2test2 = longest_string1 ["A","bc","C"] = "bc"
val ex2test3 = longest_string1 ["winner1", "winner2"] = "winner1"

val ex3test1 = longest_string2 [] = ""
val ex3test2 = longest_string2 ["A","bc","C"] = "bc"
val ex3test3 = longest_string2 ["winner1", "winner2"] = "winner2"

val ex4atest1 = longest_string3 [] = ""
val ex4atest2 = longest_string3 ["A","bc","C"] = "bc"
val ex4atest3 = longest_string3 ["winner1", "winner2"] = "winner1"

val ex4btest1 = longest_string4 [] = ""
val ex4btest2 = longest_string4 ["A","bc","C"] = "bc"
val ex4btest3 = longest_string4 ["winner1", "winner2"] = "winner2"

val ex5test1 = longest_capitalized [] = ""
val ex5test2 = longest_capitalized ["A","Bc","C"] = "Bc"
val ex5test3 = longest_capitalized ["Winner1", "Winner2"] = "Winner1"

val ex6test1 = rev_string "abc" = "cba"
val ex6test2 = rev_string "" = ""
val ex6test3 = rev_string "radar" = "radar"

val ex7test1 = first_answer (fn x => if x > 3 then SOME x else NONE) [1,2,3,4,5] = 4

val ex8test1 = all_answers (fn () => SOME [1]) [] = SOME []
val ex8test2 = all_answers (fn x => if x = 1 then SOME [x] else NONE) [2,3,4,5,6,7] = NONE
val ex8test3 = all_answers (fn x => if x mod 2 = 0 then SOME [x] else NONE) [2,3,4,5,6,7] = NONE
val ex8test4 = all_answers (fn x => if x mod 2 >= 0 then SOME [x] else NONE) [2,3,4] = SOME [2,3,4]

val ex9atest1 = count_wildcards Wildcard = 1
val ex9atest2 = count_wildcards (TupleP [Wildcard, Wildcard]) = 2
val ex9atest3 = count_wildcards (TupleP [Wildcard, Variable "hey"]) = 1

val ex9btest1 = count_wild_and_variable_lengths (Variable("a")) = 1
val ex9btest2 = count_wild_and_variable_lengths (Variable("abc")) = 3
val ex9btest3 = count_wild_and_variable_lengths (TupleP [Variable("abc"), Wildcard]) = 4

val ex9ctest1 = count_some_var ("x", Variable("x")) = 1
val ex9ctest2 = count_some_var ("x", (TupleP [Variable("x")])) = 1
val ex9ctest3 = count_some_var ("x", (TupleP [Variable("x"), Variable("y")])) = 1
val ex9ctest4 = count_some_var ("x", (TupleP [Variable("x"), Variable("x")])) = 2
val ex9ctest5 = count_some_var ("x", (TupleP [Variable("x"), Wildcard, Variable("x")])) = 2


(*
val ex10test1 = has_repeats [] = false
val ex10test2 = has_repeats ["x"] = false
val ex10test3 = has_repeats ["x", "y"] = false
val ex10test4 = has_repeats ["x", "y", "x"] = true
						  
val ex10test5 = extract_variable_strings Wildcard = []
val ex10test6 = extract_variable_strings (Variable("hoxie")) = ["hoxie"]
val ex10test7 = extract_variable_strings (TupleP []) = []
val ex10test8 = extract_variable_strings (TupleP [Variable("hoxie"), Variable("nate")]) = ["hoxie", "nate"]
val ex10test9 = extract_variable_strings (TupleP [Variable("hoxie"), Wildcard, Variable("nate")]) = ["hoxie", "nate"]
*)
											      
val ex10test1 = check_pat (Variable("x")) = true
val ex10test2 = check_pat (TupleP [Variable("hoxie"), Wildcard, Variable("nate")]) = true
val ex10test3 = check_pat (TupleP [Variable("a"), Variable("b"), Variable("a")]) = false

val ex11test1 = match (Const(1), Wildcard) = SOME []
val ex11test2 = match (Const(3), Variable("hey")) = SOME [("hey", Const(3))]
val ex11test3 = match (Constructor("abc", Const(3)), Variable("hey")) = SOME [("hey", Constructor("abc", Const(3)))]
val ex11test4 = match (Unit, UnitP) = SOME []
val ex11test5 = match (Const(3), ConstP(3)) = SOME []
val ex11test6 = match (Const(3), ConstP(17)) = NONE
val ex11test7 = match (
	Tuple([Const(3), Unit, Constructor("Age", Const(29))]),
	TupleP([ConstP(3), UnitP])
    ) = NONE  (* wrong length *)
val ex11test8 = match (
	Tuple([Const(3), Unit, Constructor("Age", Const(29))]),
	TupleP([ConstP(3), UnitP, ConstructorP("Age", ConstP(29))])
    ) = SOME []

val ex11test100 = match (Const(1), UnitP) = NONE

val test12 = first_match Unit [UnitP] = SOME []
