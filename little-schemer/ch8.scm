;; Hoxie Ackerman
;; The Little Schemer
;; Chapter 8: Lambda the Ultimate

(define (rember element lat)
  ;; Return the list formed by removing the first occurrence
  ;; of ELEMENT from LAT if it exists; return lat otherwise.
  (cond ((null? lat) '())
        ((equal? element (car lat)) (cdr lat))
        (else (cons (car lat)
                    (remove-first-occurence element (cdr lat))))))

(define (insertL new old lat)
  ;; Insert atom NEW to the left of the first occurrence of atom OLD
  ;; in list of atoms LAT.
  (cond ((null? lat) '())
        ((equal? old (car lat)) (cons new lat))
        (else (cons (car lat)
                    (insertL new old (cdr lat))))))

;; rember (above) could have been defined using either eq? or equal? as
;; the test predicate.  Different predicates will provide different 
;; functionality.  We'd like to abstract out the test predicate, so we...

(define (rember-f test? element lat)
  ;; Remove the first occurrence of element in lat, where we check
  ;; for an occurrence using test?
  (cond ((null? lat) '())
        ((test? element (car lat)) (cdr lat))
        (else (cons (car lat)
                    (rember-f test? element (cdr lat))))))

(define (equals-c? a)
  (lambda (x) (eq? a x)))

(define is-2 (equals-c? 2))
(is-2 3)
(is-2 2)

;; We can use currying to make another version of rember-f...
(define (rember-f test?)
  (lambda (element lat)
    (cond ((null? lat) '())
        ((test? element (car lat)) (cdr lat))
        (else (cons (car lat)
                    ((rember-f test?) element (cdr lat)))))))

(define rember-eq (rember-f eq?))
(define rember-equal (rember-f equal?))
(define rember-= (rember-f =))


;; Add predicate functionality to insertR and insertL
(define (insertR-f test? new old lat)
  ;; Insert atom NEW to the right of the first occurrence of atom OLD
  ;; in list LAT.
  (cond ((null? lat) '())
        ((test? old (car lat)) (cons old
                                   (cons new
                                         (cdr lat))))
        (else (cons (car lat)
                    (insertR-f test? new old (cdr lat))))))

(define (insertL-f test? new old lat)
  ;; Insert atom NEW to the left of the first occurrence of atom OLD
  ;; in list of atoms LAT, checking for equality with test?.
  (cond ((null? lat) '())
        ((test? old (car lat)) (cons new lat))
        (else (cons (car lat)
                    (insertL-f test? new old (cdr lat))))))

;; Add currying
(define (insertR-f test?)
  (lambda (new old lat)
    (cond ((null? lat) '())
          ((test? old (car lat)) (cons old
                                       (cons new
                                             (cdr lat))))
          (else (cons (car lat)
                      ((insertR-f test?) new old (cdr lat)))))))

(define insertR-eq? (insertR-f eq?))
(insertR-eq? 'hoxie 'hamilton '(hamilton ackerman))


;; All that differs between insertL and insertR is how we insert the
;; new atom with respect to the current lat.  So we abstract that
;; out with inserter functions:

(define (insert-left a lat)
  ;; Insert a to the LEFT of the first element of lat, ie index 0
  (cons a lat))
(insert-left 'hamilton '(hoxie ackerman))

(define (insert-right a lat)
  ;; Insert a to the RIGHT of the first element of lat, ie index 1
  (cons (car lat) (cons a (cdr lat))))
(insert-right 'hoxie '(hamilton ackerman))

(define (insert-f inserter)
  (lambda (new old lat)
    (cond ((null? lat) '())
          ;; If first element equals OLD, insert using INSERTER
          ((eq? old (car lat)) (inserter new lat))
          ;; Otherwise, skip first element
          (else (cons (car lat)
                      ((insert-f inserter) new old (cdr lat)))))))

((insert-f insert-right) 'hoxie 'hamilton '(hamilton ackerman))
((insert-f insert-left) 'hamilton 'hoxie '(hoxie ackerman))

;; We can do all of these with anonymous functions instead of named inserters,
;; to avoid clogging up our name space and needing to remember too much
(define insertL (insert-f (lambda (a lat) (cons a lat))))
(define insertR (insert-f (lambda (a lat) (cons (car lat)
                                                (cons a
                                                      (cdr lat))))))

(insertL 'hamilton 'hoxie '(hoxie ackerman))
(insertR 'hoxie 'hamilton '(hamilton ackerman))


;; Of course, the subst (substitute the first OLD with NEW) function looked really
;; similar... we just need an inserter that replaces the first element with a new element
(define (replace-first a lat)
  ;; Replace the first element of lat with a
  (cons a (cdr lat)))

(define subst (insert-f replace-first))
(subst 'hamilton 'hoxie '(hoxie hoxie ackerman))

;; We can also define rember (remove the first instance of OLD)
;; But rember just takes an element and a list, so we'll wrap it accordingly:
(define (remove-first a lat) (cdr lat))
(define rember
  (lambda (a lat)
    ((insert-f remove-first) #f a lat)))
(rember 'ackerman '(ackerman hamilton hoxie ackerman))


;; Let's apply this idea of abstraction to value, from Chapter 6
(define (operator nexp) (car nexp))
(define (first-exp nexp) (cadr nexp))
(define (second-exp nexp) (caddr nexp))
(define (atom? x) (and (not (pair? x)) (not (null? x))))

(define (atom-to-function x)
  (cond ((eq? x '+) +)
        ((eq? x '*) *)
        (else expt)))

(define (value nexp)
  ;; Return the value of numbered arithmetic expression nexp, where the notation used
  ;; depends on the helper functions operator, first-exp, and second-exp
  ;; (value-prefix '(+ 3 4)) => 7
  (cond ((atom? nexp) nexp)
        (else ((atom-to-function (operator nexp)) (first-exp nexp) (second-exp nexp)))))

(value '(+ 2 3))
(value '(* 2 3))
(value '(^ 2 3))


;; Next, we notice that we're passing in both a test function (eq?, equal?, etc.) and an
;; occurrence to test for (old, often).  Why not just wrap both into the test, ie let the
;; test predicate test for the presence of old?
(define (multiremberT test? lat)
  ;; Return a list of all atoms in lat satisfying test? removed
  (cond ((null? lat) '())
        ((test? (car lat)) (multiremberT test? (cdr lat)))
        (else (cons (car lat)
                    (multiremberT test? (cdr lat))))))

;; test? will encode whatever I want!
(multiremberT (lambda (x) (= x 2)) '(1 2 3))

;; It could even be more general than just checking for a single element
(define (even? n) (= (remainder n 2) 0))
(multiremberT even? '(1 2 3 4))


;; Finally, we're going to built a partition function...
(define (partition test? lat)
  ;; Return a list of two lists:
  ;; ((elements in lat s.t. test? is true) (elements s.t. test? is false))
  
  (define (partition-helper test? lat ltrue lfalse)
    (cond ((null? lat) (list (reverse ltrue) (reverse lfalse)))
          ((test? (car lat))
           (partition-helper test? (cdr lat) (cons (car lat) ltrue) lfalse))
          (else 
           (partition-helper test? (cdr lat) ltrue (cons (car lat) lfalse)))))

  (partition-helper test? lat '() '()))

(partition even? '(1 2 3 4 5))



  
