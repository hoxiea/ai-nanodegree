;; Hoxie Ackerman
;; The Little Schemer
;; Chapter 9: ...and Again, and Again, and Again,...

(define (looking a lat)
  ;; Look for atom a in list of atoms lat, where if you hit a number,
  ;; you go to that element of lat and repeat; when you hit your first
  ;; non-number, return whether it equals atom a you were looking for
  (keep-looking a (pick 1 lat) lat))

(define (keep-looking a sorn lat)
  ;; Looking for atom A in list of atoms LAT with current input
  ;; SORN = symbol or number.  We stop at the first atom we find and
  ;; compare that atom to A.
  (cond ((number? sorn)
         (keep-looking a (pick sorn lat) lat))
        (else (eq? sorn a))))

(define (pick n lat)
  (cond ((eq? n 1) (car lat))
        (else (pick (- n 1) (cdr lat)))))

(looking 'caviar '(2 3 caviar))
(looking 'caviar '(2 3 grits))

;; Looking is a PARTIAL recursive function: it's recursive, but it doesn't
;; recurse on a subset of its original input.  Thus, for some input, it seems
;; possible that we'll recurse infinitely.

;; Here's something that definitely recurses infinitely:
(define (eternity x) (eternity x))
;; eternity is the most partial function you can have, since for 0% of its
;; inputs does it reach its goal.


;; A PAIR is a list with only two S-expressions, where 
;; an S-expression is either an atom or a (possible empty) list of S-expressions
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

;; Consider SHIFT, which takes a pair whose first component is a pair and builds
;; a pair by shifting the second part of the first component into the second component
(define (shift pair)
  (build (first (first pair))
         (build (second (first pair))
                (second pair))))

(shift '((a (b c)) (d)))

;; Consider ALIGN, which
(define (align pora)
  (cond ((atom? pora) pora)
        ((pair? (first pora))
         (align (shift pora)))
        (else (build (first pora)
                     (align (second pora))))))

(align '((a c d) b))


(((lambda (length)
   (lambda (l)
     (cond ((null? l) 0)
           (else (+ 1 (length (cdr l)))))))
 eternity) '())


(lambda (n) (+ n 2))
