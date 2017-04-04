;; Hoxie Ackerman
;; The Little Schemer
;; Chapter 2: Toys

(define (atom? x) 
  (and (not (pair? x)) (not (null? x))))

;; Useful functions:
;; atom?, car, cdr, null?, eq?

(define (lat? x)
  ;; Is x a list of atoms?
  (cond ((null? x) #t)
        ((not (atom? (car x))) #f)
        (else (lat? (cdr x)))))

(and (eq? (lat? '(1 2 3)) #t)
     (eq? (lat? '(1 2 (3 4))) #f))

(define (member? element l)
  ;; Is element a top-level member of l?
  (cond ((null? l) #f)
        ((eq? element (car l)) #t)
        (else (member? element (cdr l)))))

(member? 1 '(1 2 3))
(member? 4 '(1 2 3))

(define (deep-member? element l)
  ;; Is element a member of l or any sublist in l?
  (cond ((null? l) #f)
        ((eq? element (car l)) #t)
        ((list? (car l)) (or (deep-member? element (car l))
                             (deep-member? element (cdr l))))
        (else (deep-member? element (cdr l)))))

(deep-member? 1 '(1 2 3))
(deep-member? 4 '(1 2 3))
(deep-member? 1 '((1 2) 3))
(deep-member? 4 '((((1 2))) 3))
(deep-member? 2 '(((((1) 2))) ((3) 4)))
