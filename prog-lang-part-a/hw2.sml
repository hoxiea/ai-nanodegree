(* Hoxie Ackerman, Programming Languages A, HW 2 *)

fun same_string(s1 : string, s2 : string) = s1 = s2

exception Ugly1AException

(* 1a *)
(* I couldn't find the elegant ~8 line solution - sorry! *)
fun all_except_option (s: string, xs: string list) =
  let
      fun list_contains_s (elements: string list) =
	case elements of
	    [] => false
	  | head::rest => if same_string(s, head) then true else list_contains_s(rest)

      (* list_without_s: Assumes `elements` contains exactly one occurrence of s *) 
      fun list_without_s (elements: string list) =
	case elements of
	    [] => raise Ugly1AException  (* shouldn't happen if elements contains s *)
	  | x::[] => []                  (* one element => must be equal to s *)
	  | x::rest => if same_string(x, s) then rest else x :: list_without_s(rest)
  in
      if list_contains_s(xs)
      then SOME(list_without_s xs)
      else NONE
  end


(* 1b *)
fun get_substitutions1(subs: string list list, s: string): string list =
  case subs of
      [] => []
    | head::rest => case all_except_option(s, head) of
			NONE => get_substitutions1(rest, s)
		      | SOME l => l @ get_substitutions1(rest, s)
	   
(* 1c *)
fun get_substitutions2(subs: string list list, s: string): string list =
  let
      fun get_sub_helper(remaining_subs: string list list, acc: string list) =
	case remaining_subs of
	    [] => acc
	  | head::rest => case all_except_option(s, head) of
			      NONE => get_sub_helper(rest, acc)
			    | SOME l => get_sub_helper(rest, acc @ l)
  in
      get_sub_helper(subs, [])
  end

(* 1d *)
fun similar_names(subs: string list list, {first = f, middle = m, last = l}) =
  let
      fun change_first_name (new_first: string) = {first = new_first, middle = m, last = l}
      fun make_first_name_substitutions (alts: string list) =
	case alts of
	    [] => []
	  | sub::rest => change_first_name(sub) :: make_first_name_substitutions(rest)
  in
      {first=f, middle=m, last=l} :: make_first_name_substitutions(get_substitutions2(subs, f))
  end


(* you may assume that Num is always used with values 2, 3, ..., 10
   though it will not really come up *)
datatype suit = Clubs | Diamonds | Hearts | Spades
datatype rank = Jack | Queen | King | Ace | Num of int 
type card = suit * rank

datatype color = Red | Black
datatype move = Discard of card | Draw 

exception IllegalMove

(* 2a *)
fun card_color (Clubs, _) = Black
  | card_color (Spades, _) = Black
  | card_color (Diamonds, _) = Red
  | card_color (Hearts, _) = Red

(* 2b *)
fun card_value (_, Num n) = n
  | card_value (_, Ace) = 11
  | card_value (_, _) = 10
			    
(* 2c *)
(* The type parameter on c tells the compiler enough to avoid a polyEqual warning *)
fun remove_card (cs, c: card, e) = 
  case cs of
      [] => raise e
    | head::rest => if (head = c) then rest else head::remove_card(rest, c, e)


(* 2d *)
fun all_same_color (cs) = 
  case cs of
      [] => true
    | _::[] => true
    | x::y::rest => card_color(x) = card_color(y) andalso all_same_color(y::rest)

(* 2e *)
fun sum_cards (cs) = 
  let
      fun sum_helper ([], acc) = acc
	| sum_helper (x::rest, acc) = sum_helper(rest, acc + card_value(x))
  in
      sum_helper(cs, 0)
  end

(* 2f *)
fun score (held_cards, goal) = 
  let
      val sum = sum_cards(held_cards)
      val prelim_score =
	  if sum > goal
	  then 3 * (sum - goal)
	  else goal - sum
  in
      if all_same_color(held_cards)
      then prelim_score div 2
      else prelim_score
  end

(* 2g *)
fun officiate (deck: card list, moves: move list, goal: int): int = 
  let
      fun officiate_helper(current_deck, current_hand, remaining_moves) =
	case (current_deck, remaining_moves) of
	    (_, [])              => score(current_hand, goal)
	  | (_, (Discard c)::ms) => officiate_helper(current_deck, remove_card(current_hand, c, IllegalMove), ms) 
	  | ([], (Draw)::_)      => score(current_hand, goal)
	  | (top_card::rest_of_deck, (Draw)::other_moves) =>
	    let
		val updated_hand = top_card :: current_hand
	    in
		if sum_cards(updated_hand) > goal
		then score(updated_hand, goal)
		else officiate_helper(rest_of_deck, updated_hand, other_moves)
	    end
  in
      officiate_helper(deck, [], moves)
  end
