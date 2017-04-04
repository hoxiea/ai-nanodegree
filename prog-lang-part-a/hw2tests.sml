(* Homework2 Simple Test *)
(* These are basic test cases. Passing these tests does not guarantee that your code will pass the actual homework grader *)
(* To run the test, add a new line to the top of this file: use "homeworkname.sml"; *)
(* All the tests should evaluate to true. For example, the REPL should say: val test1 = true : bool *)

use "hw2.sml";


val ex1a_test1 = all_except_option ("string", ["hoxie"]) = NONE
val ex1a_test2 = all_except_option ("string", ["string"]) = SOME []
val ex1a_test3 = all_except_option ("string", ["string", "hoxie"]) = SOME ["hoxie"]
val ex1a_test4 = all_except_option ("string", ["hoxie", "string", "blah"]) = SOME ["hoxie", "blah"]

val ex1b_test1 = get_substitutions1 ([["foo"],["there"]], "blah") = []
val ex1b_test2 = get_substitutions1 ([["foo"],["there"]], "foo") = []
val ex1b_test3 = get_substitutions1 ([["Fred", "Frederick"],["Eliz", "Betty"],["Freddie", "Fred", "F"]], "Fred") = ["Frederick", "Freddie", "F"]
val ex1b_test4 = get_substitutions1 ([["Fred", "Frederick"],["Jeff", "Jeffrey"],["Geoff", "Jeff", "Jeffrey"]], "Jeff") =
		 ["Jeffrey", "Geoff", "Jeffrey"]

val ex1c_test1 = get_substitutions2 ([["foo"],["there"]], "blah") = []
val ex1c_test2 = get_substitutions2 ([["foo"],["there"]], "foo") = []
val ex1c_test3 = get_substitutions2 ([["Fred", "Frederick"],["Eliz", "Betty"],["Freddie", "Fred", "F"]], "Fred") =
		 ["Frederick", "Freddie", "F"]
val ex1c_test4 = get_substitutions2 ([["Fred", "Frederick"],["Jeff", "Jeffrey"],["Geoff", "Jeff", "Jeffrey"]], "Jeff") =
		 ["Jeffrey", "Geoff", "Jeffrey"]

val test4 = similar_names ([["Fred","Fredrick"],["Elizabeth","Betty"],["Freddie","Fred","F"]], {first="Fred", middle="W", last="Smith"}) =
	    [{first="Fred", last="Smith", middle="W"}, 
             {first="Fredrick", last="Smith", middle="W"},
	     {first="Freddie", last="Smith", middle="W"},
	     {first="F", last="Smith", middle="W"}]

val ex2a_test1 = card_color (Clubs, Num 2) = Black
val ex2a_test2 = card_color (Spades, King) = Black
val ex2a_test3 = card_color (Hearts, Ace) = Red
val ex2a_test4 = card_color (Diamonds, Num 8) = Red

val ex2b_test1 = card_value (Clubs, Num 2) = 2
val ex2b_test2 = card_value (Hearts, Num 8) = 8
val ex2b_test3 = card_value (Hearts, Ace) = 11
val ex2b_test4 = card_value (Hearts, King) = 10
val ex2b_test5 = card_value (Hearts, Queen) = 10


val cardlist = [(Hearts, Ace), (Hearts, King), (Hearts, Ace)]
val ex2c_test1 = remove_card ([hd cardlist], (Hearts, Ace), IllegalMove) = []
val ex2c_test2 = remove_card (cardlist, (Hearts, Ace), IllegalMove) = tl cardlist
val ex2c_test3 = (remove_card (cardlist, (Diamonds, Ace), IllegalMove)
		 handle IllegalMove => []) = []

val ex2d_test1 = all_same_color [(Hearts, Ace), (Hearts, Ace)] = true
val ex2d_test2 = all_same_color [(Hearts, Ace), (Diamonds, Num 3)] = true
val ex2d_test3 = all_same_color [(Spades, Ace), (Diamonds, Num 3)] = false
val ex2d_test4 = all_same_color [(Spades, Ace)] = true
val ex2d_test5 = all_same_color [(Spades, Ace), (Clubs, Num 3), (Hearts, Queen)] = false

val ex2e_test1 = sum_cards [] = 0
val ex2e_test2 = sum_cards [(Hearts, Ace)] = 11
val ex2e_test3 = sum_cards [(Hearts, Num 2), (Diamonds, King)] = 12
val ex2e_test4 = sum_cards [(Hearts, Num 2), (Diamonds, King), (Spades, Ace)] = 23

val ex2f_test1 = score ([(Hearts, Num 2),(Clubs, Num 4)], 10) = 4


val test11 = officiate ([(Hearts, Num 2),(Clubs, Num 4)],[Draw], 15) = 6

val test12 = officiate ([(Clubs,Ace),(Spades,Ace),(Clubs,Ace),(Spades,Ace)],
                        [Draw,Draw,Draw,Draw,Draw],
                        42)
             = 3

val test13 = ((officiate([(Clubs,Jack),(Spades,Num(8))],
                         [Draw,Discard(Hearts,Jack)],
                         42);
               false) 
              handle IllegalMove => true)
             
(* 
val coa1 = cons_onto_all(3, [[2, 1], [10, ~6, 4], []]) = [[3, 2, 1], [3, 10, ~6, 4], [3]]

val ea1 = expand_aces [] = [[]]
val ea2 = expand_aces [(Hearts, Num 7)] = [[(Hearts, Num 7)]]
val ea3 = expand_aces [(Hearts, Num 7), (Diamonds, Queen)] = [[(Hearts, Num 7), (Diamonds, Queen)]]
val ea4 = expand_aces [(Hearts, Num 7), (Spades, Ace), (Diamonds, Queen)] = [
	[(Hearts, Num 7), (Spades, Num 1), (Diamonds, Queen)],
	[(Hearts, Num 7), (Spades, Num 11), (Diamonds, Queen)]
    ]

*)
