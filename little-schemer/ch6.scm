;; Hoxie Ackerman
;; The Little Schemer
;; Chapter 6: Shadows

(define (atom? x) 
  (and (not (pair? x)) (not (null? x))))

;; Useful functions:
;; atom?, car, cdr, null?, eq?, zero?

;; An arithmetic expression is either an atom (including numbers)
;; OR two arthimetic expressions combined by +, x, or ^.

;; (n + 3) isn't an arithmetic expression bc of the parentheses.
;; But it's a good representation for the arth expr: n + 3

;; numbered? determines whether a representation of an arithmetic expression
;; contains only numbers besides the operators
;; So (numbered? (3 + (4 * 5))) is true
;;    (numbered? (hoxie + (4 * 5))) is false, since hoxie might be point to a number but it's not one itself

(define (numbered? aexp)
  ;; Does arithmetic expression aexp contain only numbers and operators?
  (cond ((atom? aexp) (number? aexp))
        ;; so aexp is two aexps combined by +, x, or ^
        ;; will be numbered if first and third elements are numbered
        (else (and (numbered? (car aexp)) (numbered? (caddr aexp))))))

;; (value aexp) should return the natural value of a numbered arithmetic expression aexp

(define (value-infix nexp)
  ;; Return the value of numbered arithmetic expression nexp, where nexp uses infix operators
  ;; (value-infix '(3 + 4)) => 7
  (cond ((atom? nexp) nexp)
        ((eq? (cadr nexp) '+) (+ (value-infix (car nexp)) (value-infix (caddr nexp))))
        ((eq? (cadr nexp) '*) (* (value-infix (car nexp)) (value-infix (caddr nexp))))
        ((eq? (cadr nexp) '^) (expt (value-infix (car nexp)) (value-infix (caddr nexp))))))

(value-infix '(2 + 3))
(value-infix '(2 + (3 * 6)))

;; Of course, there's nothing special about infix notation.  We could also write a
;; value function that works with nexps in prefix notation...

(define (value-prefix nexp)
  ;; Return the value of numbered arithmetic expression nexp, where nexp uses prefix operators
  ;; (value-prefix '(+ 3 4)) => 7
  (cond ((atom? nexp) nexp)
        ((eq? (car nexp) '+) (+ (value-prefix (cadr nexp)) (value-prefix (caddr nexp))))
        ((eq? (car nexp) '*) (* (value-prefix (cadr nexp)) (value-prefix (caddr nexp))))
        ((eq? (car nexp) '^) (expt (value-prefix (cadr nexp)) (value-prefix (caddr nexp))))))

(value-prefix '(^ 2 (+ 4 (* 2 3))))

;; Of course, it would be better to define helper functions to abstract out what's happening in
;; both value-infix and value-prefix
(define (operator nexp) (car nexp))
(define (first-exp nexp) (cadr nexp))
(define (second-exp nexp) (caddr nexp))

(define (value nexp)
  ;; Return the value of numbered arithmetic expression nexp, where the notation used
  ;; depends on the helper functions operator, first-exp, and second-exp
  ;; (value-prefix '(+ 3 4)) => 7
  (cond ((atom? nexp) nexp)
        ((eq? (operator nexp) '+) (+ (value-prefix (first-exp nexp)) (value-prefix (second-exp nexp))))
        ((eq? (operator nexp) '*) (* (value-prefix (first-exp nexp)) (value-prefix (second-exp nexp))))
        ((eq? (operator nexp) '^) (expt (value-prefix (first-exp nexp)) (value-prefix (second-exp nexp))))))

(value '(+ 2 5))


;; We've seen representations already.  For example, '4 represents the concept for four.
;; We could have used other things, for example the number of empty parenthesis pairs
;; in a list...
;; zero  -> ()
;; one   -> (())
;; two   -> (() ())
;; three -> (() () ())
;; And to work with natural numbers, all we needed were number?, zero?, add1, and sub1

(define (sero? n) (null? n))
(sero? '())
(sero? '(()))

(define (edd1 n) (cons '() n)
(edd1 '())
(edd1 '(() () ()))

(define (zub1 n) (cdr n))
(zub1 '(() () () ()))
