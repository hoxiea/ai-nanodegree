(* Hoxie Ackerman, Coursera Programming Languages Part A, Homework 3 *)

fun only_capitals strings =
  List.filter (fn s => Char.isUpper(String.sub(s, 0))) strings

fun longest_string1 strings =
  List.foldl (
      fn (s, acc) => if String.size(s) > String.size(acc) then s else acc
  ) "" strings

fun longest_string2 strings =
  List.foldl (
      fn (s, acc) => if String.size(s) >= String.size(acc) then s else acc
  ) "" strings

fun longest_string_helper f strings =
  List.foldl (
      fn (s, acc) => if f (String.size(s), String.size(acc)) then s else acc
  ) "" strings
	     
val longest_string3 = longest_string_helper (fn (x, y) => x > y)
val longest_string4 = longest_string_helper (fn (x, y) => x >= y)

val longest_capitalized = longest_string1 o only_capitals

val rev_string = String.implode o List.rev o String.explode
  

exception NoAnswer

(* Return the v from the first SOME v when applying f to xs *)
fun first_answer f xs =
  case xs of
      [] => raise NoAnswer
    | x::rest => case f(x) of
		     NONE => first_answer f rest
		   | SOME v => v
				 

fun all_answers f xs =
  let fun helper(remaining_xs, acc) =
	case (remaining_xs, acc) of
	    ([], _) => acc
	  | (_, NONE) => NONE  (* shouldn't happen, but makes match exhaustive *)
	  | (x::rest, SOME so_far) => case f(x) of
					  NONE => NONE
					| SOME elems => helper(rest, SOME (so_far @ elems))
  in helper(xs, SOME [])
  end
      
      

datatype pattern = Wildcard
		 | Variable of string
		 | UnitP
		 | ConstP of int
		 | TupleP of pattern list
		 | ConstructorP of string * pattern

datatype valu = Const of int
	      | Unit
	      | Tuple of valu list
	      | Constructor of string * valu

(*
  f1: unit -> int
  f2: string -> int
  p: pattern
  PRODUCES: int
*)
fun g f1 f2 p =
    let 
	val r = g f1 f2 
    in
	case p of
	    Wildcard          => f1 ()
	  | Variable x        => f2 x
	  | TupleP ps         => List.foldl (fn (p,i) => (r p) + i) 0 ps
	  | ConstructorP(_,p) => r p
	  | _                 => 0
    end

(* How many Wildcards? Wildcards count for 1, variable names count for 0 *)
val count_wildcards = g (fn () => 1) (fn s => 0)

(* Wildcards count for 1, variable names count for their length *)
val count_wild_and_variable_lengths = g (fn () => 1) (fn s => String.size(s))

(* How many times does Variable value `s` appear in pattern p? *)
fun count_some_var (s, p) =
  g (fn () => 0) (fn x => if x = s then 1 else 0) p


(* Are all the strings used for variables unique? *)
fun check_pat pattern =
  let
      fun has_repeats strings =
	case strings of
	    [] => false
	  | x::rest => if List.exists (fn elem => elem = x) rest
		       then true
		       else has_repeats rest

      fun extract_variable_strings p =
	case p of
	    Variable x => [x]
	  | TupleP ps => List.foldl (fn (p',acc) => acc @ (extract_variable_strings p')) [] ps
	  | _ => []
  in
      (not o has_repeats o extract_variable_strings) pattern
  end

fun match (v, p) =
  case (v, p) of
      (_, Wildcard) => SOME []
    | (_, Variable s) => SOME [(s, v)]
    | (Unit, UnitP) => SOME []
    | (Const m, ConstP n) => if m = n then SOME [] else NONE
    | (Constructor (s2,v), ConstructorP (s1,p)) => if s1 = s2 then match(v, p) else NONE
    | (Tuple vs, TupleP ps) =>
      if List.length(vs) = List.length(ps)
      then all_answers match (ListPair.zip (vs, ps))
      else NONE
    | _ => NONE


fun first_match v ps =
  let
      fun matcher p = match(v, p)   (* partial function application *)
  in SOME(first_answer matcher ps)
     handle NoAnswer => NONE
  end
  

(**** for the challenge problem only ****)
datatype typ = Anything
	     | UnitT
	     | IntT
	     | TupleT of typ list
	     | Datatype of string
