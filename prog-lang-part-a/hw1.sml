(* Hoxie Ackerman
   Programming Languages, Part A
   Assignment 1 *)

(* dates are of the form (year, month, day) *)
fun get_year(d: int*int*int) = #1 d
fun get_month(d: int*int*int) = #2 d
fun get_day(d: int*int*int) = #3 d

(* #1 *)
fun is_older (date1: int*int*int, date2: int*int*int) =
  (* Does date1 come before date2? *)
  let
      val year1 = get_year date1
      val year2 = get_year date2
      val month1 = get_month date1
      val month2 = get_month date2
      val day1 = get_day date1
      val day2 = get_day date2
  in
      (year1 < year2) orelse
      (year1 = year2 andalso month1 < month2) orelse
      (year1 = year2 andalso month1 = month2 andalso day1 < day2)
  end

(* #2 *)
fun number_in_month (dates: (int*int*int) list, month: int) =
  if null dates
  then 0
  else if get_month(hd dates) = month
  then 1 + number_in_month(tl dates, month)
  else number_in_month(tl dates, month)

(* #3 *)
fun number_in_months (dates: (int*int*int) list, months: int list) =
  if null months
  then 0
  else number_in_month(dates, hd months) + number_in_months(dates, tl months)

(* #4 *)
fun dates_in_month (dates: (int*int*int) list, month: int) =
  if null dates
  then []
  else if get_month(hd(dates)) = month
  then (hd dates) :: dates_in_month(tl dates, month)
  else dates_in_month(tl dates, month)

(* #5 *)
fun dates_in_months (dates: (int*int*int) list, months: int list) =
  if null months
  then []
  else dates_in_month(dates, hd months) @ dates_in_months(dates, tl months)

(* #6 *)
fun get_nth (xs: string list, n: int) =
  if n = 1
  then hd xs
  else get_nth(tl xs, n - 1)

(* #7 *)
fun date_to_string (d: int*int*int) =
  let
      val monthlist = ["January", "February", "March", "April",
		       "May", "June", "July", "August",
		       "September", "October", "November", "December"]
      val monthstring = get_nth(monthlist, get_month d)
      val daystring = Int.toString(get_day(d))
      val yearstring = Int.toString(get_year(d))
  in
      monthstring ^ " " ^ daystring ^ ", " ^ yearstring
  end

(* #8 *)
fun number_before_reaching_sum (sum: int, nums: int list) =
  let
      val took_enough_nums = hd nums >= sum
  in
      if took_enough_nums
      then 0
      else 1 + number_before_reaching_sum(sum - hd nums, tl nums)
  end

(* #9 *)
fun what_month (day_of_year: int) =
  let
      val number_of_days = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31]
  in
      1 + number_before_reaching_sum(day_of_year, number_of_days)
  end

(* #10 *)
fun month_range (day1: int, day2: int) =
  if day1 > day2
  then []
  else what_month(day1) :: month_range(day1 + 1, day2)


(* #11 *)
fun oldest (dates: (int*int*int) list) =
  let
      fun oldest_at_least_one (date1: int*int*int, rest: (int*int*int) list) =
	(* date1 must be a date; rest can contain 0 or more dates *)
	if null rest
	then date1
	else if is_older(date1, hd rest)
	then oldest_at_least_one(date1, tl rest)
	else oldest_at_least_one(hd rest, tl rest)
  in
      if null dates
      then NONE
      else SOME(oldest_at_least_one(hd dates, tl dates))
  end 


(* #12 *)
fun contains (xs: int list, x: int) =
  if null xs then false
  else if hd xs = x then true
  else contains(tl xs, x)

fun reverse (xs: int list) =
  if null xs
  then []
  else reverse(tl xs) @ [hd xs]

fun unique (xs: int list) =
  let
      fun unique_helper(unique_so_far: int list, remaining: int list) =
	if null remaining
	then unique_so_far
	else if contains(unique_so_far, hd remaining)
	then unique_helper(unique_so_far, tl remaining)
	else unique_helper((hd remaining) :: unique_so_far, tl remaining)
  in
      reverse(unique_helper([], xs))
  end
	
fun number_in_months_challenge (dates: (int*int*int) list, months: int list) =
  number_in_months(dates, unique(months))

fun dates_in_months_challenge (dates: (int*int*int) list, months: int list) =
  dates_in_months(dates, unique(months))


(* #13: reasonable_date *)
fun get_nth_int(ns: int list, m: int) =
  (* 1-indexed!*)
  if m = 1 then hd ns
  else get_nth_int(tl ns, m - 1)

fun reasonable_date(date: int*int*int) =
  let
      val year = get_year(date)
      val month = get_month(date)
      val day = get_day(date)

      fun is_leap_year(y: int) =
	(y mod 400 = 0) orelse (y mod 4 = 0 andalso y mod 100 <> 0)
      val days_per_month =
	  if is_leap_year(year)
	  then [31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31]
	  else [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31]
      
      fun year_ok(y: int) = y > 0
      fun month_ok(m: int) = 1 <= m andalso m <= 12
      fun day_ok(d: int) = d <= get_nth_int(days_per_month, month)
  in
      year_ok(year) andalso month_ok(month) andalso day_ok(day)
  end
