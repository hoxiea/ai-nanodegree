;; Hoxie Ackerman
;; The Little Schemer
;; Chapter 3: Cons the Magnificent

(define (atom? x) 
  (and (not (pair? x)) (not (null? x))))

;; Useful functions:
;; atom?, car, cdr, null?, eq?

(define (remove-first-occurence element lat)
  ;; Return the list formed by removing the first occurrence
  ;; of ELEMENT from LAT if it exists; return lat otherwise.
  (cond ((null? lat) '())
        ((eq? element (car lat)) (cdr lat))
        (else (cons (car lat)
                    (remove-first-occurence element (cdr lat))))))


(remove-first-occurence 1 '(1 2 3 2 1))
(remove-first-occurence 4 '(1 2 3 2 1))
(remove-first-occurence 2 '(1 2 3 2 1))
                                             
(define (firsts l)
  ;; Given l, a list that's either empty or contains only lists,
  ;; return a list containing the first element of each of l's lists
  (cond ((null? l) '())
        (else (cons (car (car l))
                    (firsts (cdr l))))))

(firsts '((a b c) (d e f)))


(define (insertR new old lat)
  ;; Insert atom NEW to the right of the first occurrence of atom OLD
  ;; in list LAT.
  (cond ((null? lat) '())
        ((eq? (car lat) old) (cons old
                                   (cons new
                                         (cdr lat))))
        (else (cons (car lat)
                    (insertR new old (cdr lat))))))

(insertR 'hoxie 'hamilton '(hamilton ackerman))
(insertR 'hoxie 'hamilton '(nathaniel ackerman))


(define (insertL new old lat)
  ;; Insert atom NEW to the left of the first occurrence of atom OLD
  ;; in list of atoms LAT.
  (cond ((null? lat) '())
        ((eq? old (car lat)) (cons new lat))
        (else (cons (car lat)
                    (insertL new old (cdr lat))))))

(insertL 'hamilton 'hoxie '(hoxie ackerman))
(insertL 'hamilton 'hoxie '(nathaniel ackerman))

(define (substitute new old lat)
  ;; Replace the first occurrence of OLD with NEW in LAT.
  (cond ((null? lat) '())
        ((eq? (car lat) old) (cons new
                                  (cdr lat)))
        (else (cons (car lat)
                    (substitute new old (cdr lat))))))

(substitute 'hoxie 'hamilton '(hamilton ackerman))
(substitute 'hoxie 'hamilton '(nathaniel ackerman))


(define (substitute-all-flat new old lat)
  ;; Replace every occurrence of OLD with NEW in (flat list) LAT.
  (cond ((null? lat) '())
        ((eq? (car lat) old) (cons new (substitute-all-flat new old (cdr lat))))
        (else (cons (car lat) (substitute-all-flat new old (cdr lat))))))

(substitute-all-flat 4 2 '(1 2 1 1 2))
(substitute-all-flat 2 3 '(1 2 1 1 2))


(define (remove-all element lat)
  ;; Remove every occurrence of ELEMENT from LAT.
  (cond ((null? lat) '())
        ((eq? (car lat) element) (remove-all element (cdr lat)))
        (else (cons (car lat)
                    (remove-all element (cdr lat))))))

(remove-all 1 '(1 2 3 2 1))

