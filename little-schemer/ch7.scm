;; Hoxie Ackerman
;; The Little Schemer
;; Chapter 7: Collections

;; A set is a lat in which nothing appears more than once

(define (member? a lat)
  ;; Is a a member of lat?
  (cond ((null? lat) #f)
        ((equal? (car lat) a) #t)
        (else (member? a (cdr lat)))))

(define (set? lat)
  ;; Is lat a set, ie every element occurs at most once?
  (cond ((null? lat) #t)
        ((member? (car lat) (cdr lat)) #f)
        (else (set? (cdr lat)))))

(set? '(1 2 3 4))
(set? '(3 hamilton 19 hoxie 2 ackerman))
(set? '(3 hamilton 19 hoxie 2 hoxie))


(define (makeset lat)
  ;; Turn lat into a set by removing duplicate entries
  (cond ((null? lat) '())
        ((member? (car lat) (cdr lat)) (makeset (cdr lat)))
        (else (cons (car lat)
                    (makeset (cdr lat))))))

(makeset '(1 2 3 4 3 2 1))


(define (subset? set1 set2)
  ;; Is set1 a subset of set2?
  (cond ((null? set1) #t)
        ((not (member? (car set1) set2)) #f)
        (else (subset? (cdr set1) set2))))

(subset? '(1 2 3) '(0 1 2 3 4))
(subset? '(1 2 5) '(0 1 2 3 4))

(define (subset? set1 set2)
  ;; Another implementation of subset?, this time using and
  (cond ((null? set1) #t)
        (else (and (member? (car set1) set2)
                   (subset? (cdr set1) set2)))))

(define (eqset? set1 set2)
  ;; Does set1 equal set2?  (Hooray math!)
  (and (subset? set1 set2) (subset? set2 set1)))

(define (intersect? set1 set2)
  ;; Is the intersection of set1 and set2 non-empty?
  (cond ((null? set1) #f)
        ((member? (car set1) set2) #t)
        (else (intersect? (cdr set1) set2))))

(define (intersect? set1 set2)
  ;; Another implementation, this one using or
  (cond ((null? set1) #f)
        (else (or (member? (car set1) set2)
                  (intersect? (cdr set1) set2)))))


(define (intersect set1 set2)
  ;; Calculate the intersection of set1 and set2
  (cond ((null? set1) '())
        ((member? (car set1) set2)
         (cons (car set1) (intersect (cdr set1) set2)))
        (else (intersect (cdr set1) set2))))

(intersect '(1 2 3) '(2 3 4))

(define (union set1 set2)
  ;; Return the union of set1 and set2
  (cond ((null? set1) set2)
        ((member? (car set1) set2)
         (union (cdr set1) set2))
        (else (cons (car set1)
                    (union (cdr set1) set2)))))

(union '(1 2 3) '(3 4 5))


(define (intersect-all l-set)
  ;; Return the intersection of all sets in list l-set
  (cond ((null? (cdr l-set)) (car l-set))
        (else (intersect (car l-set) (intersect-all (cdr l-set))))))

(intersect-all '((1 2 3) (2 3 4) (3 4 5)))

;; A PAIR is a list with only two S-expressions, where 
;; an S-expression is either an atom or a (possible empty)
;; list of S-expressions
(define (pair? x)
  (cond ((atom? x) #f)
        ((null? x) #f)
        ((null? (cdr x)) #f)
        ((null? (cddr x)) #t)
        (else #f)))

;; Now we'll abstract out the stuff we need to work with pairs
(define (first p) (car p))
(define (second p) (cadr p))
(define (build s1 s2) (cons s1 (cons s2 '())))
(define (third l) (caddr l))

;; A RELATION is a set of unique pairs
;; A RELATION is a function if no x maps to different ys, i.e.
;;   (fun? rel) iff (set? (firsts rel))
(define (firsts rel)
  ;; Return a list of the first element of each pair in REL
  (cond ((null? rel) '())
        (else (cons (car (car rel))
                    (firsts (cdr rel))))))

(firsts '((1 2) (3 4) (5 6) (7 8)))

(define (fun? rel)
  ;; Is relation rel a function?
  (set? (firsts rel)))


(define (reverse-relation rel)
  ;; Return a list of the inverted pairs of rel
  (cond ((null? rel) '())
        (else (cons (build (second (car rel))
                           (first (car rel)))
                    (reverse-relation (cdr rel))))))

(reverse-relation '((1 2) (3 4) (5 6) (7 8)))
  
;; Of course, we can abstract further with a reverse-pair function...
(define (reverse-pair p)
  (build (second p) (first p)))

(reverse-pair '(2 1))

(define (reverse-relation rel)
  ;; Return a list of the inverted pairs of rel
  (cond ((null? rel) '())
        (else (cons (reverse-pair (car rel))
                    (reverse-relation (cdr rel))))))

;; Now we wonder about one-to-one-ness of a relation:
(define (full-function rel)
  (and (fun? rel) (fun? (reverse-relation rel))))
