;; Hoxie Ackerman
;; The Little Schemer
;; Chapter 4: Numbers Games

(define (atom? x) 
  (and (not (pair? x)) (not (null? x))))

;; Useful functions:
;; atom?, car, cdr, null?, eq?, zero?

(define (add1 n) (+ n 1))
(define (sub1 n) (- n 1))
(zero? 0)

(define (plus a b)
  (cond ((= a 0) b)
        (else (plus (sub1 a) (add1 b)))))

(plus 10 20)

(define (subtract a b)
  (cond ((= b 0) a)
        (else (subtract (sub1 a) (sub1 b)))))

(subtract 17 4)

;; A tuple is a flat list of zero or more numbers

(define (addtup tuple)
  (cond ((null? tuple) 0)
        (else (+ (car tuple) (addtup (cdr tuple))))))

(addtup '(1 2 3 4))

(define (multiply a b)
  (cond ((or (zero? a) (zero? b)) 0)
        ((eq? b 1) a)
        (else (+ a (multiply a (sub1 b))))))

(define (multiply a b)
  (cond ((zero? b) 0)
        (else (+ a (multiply a (sub1 b))))))

(multiply 7 4)
(multiply 7 0)

(define (add-tuples-same-length tup1 tup2)
  ;; Add two tuples of equal length
  (cond ((null? tup1) '())
        (else (cons (+ (car tup1) (car tup2))
                    (add-tuples-same-length (cdr tup1) (cdr tup2))))))

(add-tuples-same-length '(3 4 5) '(5 4 3))


(define (add-tuples tup1 tup2)
  ;; Add as many elements of tup1 and tup2 as you can before one runs out
  (cond ((or (null? tup1) (null? tup2)) '())
        (else (cons (+ (car tup1) (car tup2))
                    (add-tuples (cdr tup1) (cdr tup2))))))

(add-tuples '(3 4 5) '(5 4 3))
(add-tuples '(3 4 5) '(5 4 3 2))
(add-tuples '(3 4 5 20) '(5 4 3))

;; The version that they write basically pads the shorter tuple with zeros,
;; which is equivalent to carrying over the rest of the non-empty tuple after
;; one goes empty.
