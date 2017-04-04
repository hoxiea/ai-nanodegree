;; Hoxie Ackerman
;; The Little Schemer
;; Chapter 5: Oh My Gawd, It's Full of Stars

(define (atom? x) 
  (and (not (pair? x)) (not (null? x))))

;; Useful functions:
;; atom?, car, cdr, null?, eq?, zero?

(define (rember* element l)
  ;; Remove all instances of element from l, at all depths
  (cond ((null? l) '())
        ((atom? (car l))
         (cond ((eq? (car l) element) (rember* element (cdr l)))
               (else (cons (car l) (rember* element (cdr l))))))
        (else (cons (rember* element (car l))
                    (rember* element (cdr l))))))

(rember* 2 '(1 2 3 4))
(rember* 2 '((((1 2) (2 (3))) (2 4))))

(define (insertR* new old l)
  ;; Everywhere an OLD appears in l (at any depth), insert
  ;; a NEW to the right of it (at the same depth)
  (cond ((null? l) '())
        ((atom? (car l))
         (cond ((eq? (car l) old) (cons old
                                        (cons new
                                              (insertR* new old (cdr l)))))
               (else (cons (car l)
                           (insertR* new old (cdr l))))))
        (else (cons (insertR* new old (car l))
                    (insertR* new old (cdr l))))))

(insertR* 'roast 'chuck '((how much (wood)) could ((a (wood) chuck)) (((chuck)))
                          (if (a) ((wood chuck))) could chuck wood))

(define (occur* a l)
  ;; How many times does atom a occur in list l?
  (cond ((null? l) 0)
        ((atom? (car l))
         (cond ((eq? a (car l)) (+ 1 (occur* a (cdr l))))
               (else (occur* a (cdr l)))))
        (else (+ (occur* a (car l))
                 (occur* a (cdr l))))))

(occur* 'banana '(((banana) (banana split) ((banana) sherbet)) (banana ((bread))) banana))

(define (subst* new old l)
  ;; Replace every occurrence of OLD with NEW in arbitrarily-nested l.
  (cond ((null? l) '())
        ((atom? (car l))
         (cond ((eq? (car l) old) (cons new
                                        (subst* new old (cdr l))))
               (else (cons (car l) (subst* new old (cdr l))))))
        (else (cons (subst* new old (car l))
                    (subst* new old (cdr l))))))

(subst* 'hoxie 'hamilton '((hamilton) ackerman ((is (named) after) alexander) hamilton))


(define (insertL* new old l)
  ;; Everywhere an OLD appears in l (at any depth), insert
  ;; a NEW to the left of it (at the same depth)
  (cond ((null? l) '())
        ((atom? (car l))
         (cond ((eq? (car l) old) (cons new
                                        (cons old
                                              (insertL* new old (cdr l)))))
               (else (cons (car l)
                           (insertL* new old (cdr l))))))
        (else (cons (insertL* new old (car l))
                    (insertL* new old (cdr l))))))

(insertL* 'roast 'chuck '((how much (wood)) could ((a (wood) chuck)) (((chuck)))
                          (if (a) ((wood chuck))) could chuck wood))


(define (member* a l)
  ;; Does a appear somewhere in l?
  (cond ((null? l) #f)
        ((atom? (car l))
         (cond ((eq? (car l) a) #t)
               (else (member* a (cdr l)))))
        (else (or (member* a (car l))
                  (member* a (cdr l))))))

(member* 'chips '((potato (chips ((with) fish) (chips)))))
(member* 'crisps '((potato (chips ((with) fish) (chips)))))

(define (leftmost l)
  (cond ((null? l) '(NO ANSWER))
        ((atom? (car l)) (car l))
        (else (leftmost (car l)))))

(leftmost '((((hot) (tuna (and))) cheese)))
(leftmost '(((() (tuna (and))) cheese)))

null     null
atom  x  atom
list     list

(define (eqlist? l1 l2)
  ;; Do lists l1 and l2 contain all the same elements at all the same depths?
  (cond ((null? l1) (null? l2))
        ;; l1 isn't null
        ((null? l2) #f)
        ;; neither l1 nor l2 are null
        ((atom? (car l1))
         (cond ((and (atom? (car l2)) (eqan? (car l1) (car l2)))
                (eqlist? (cdr l1) (cdr l2)))
               (else #f)))
        ;; (car l1) is a list
        (else (cond ((atom? (car l2)) #f)
                    (else (and (eqlist? (car l1) (car l2))
                               (eqlist? (cdr l1) (cdr l2))))))))

(define (eqlist? l1 l2)
  ;; Do lists l1 and l2 contain all the same elements at all the same depths?
  (cond ((and (null? l1) (null? l2)) #t)
        ((or (null? l1) (null? l2))  #f)
        ;; neither l1 nor l2 are null
        ((and (atom? (car l1)) (atom? (car l2)))
         (and (eq? (car l1) (car l2))
              (eqlist? (cdr l1) (cdr l2))))
        ((or (atom? (car l1)) (atom? (car l2))) #f)
        ;; both (car l1) and (car l2) are lists
        (else (and (eqlist? (car l1) (car l2))
                   (eqlist? (cdr l1) (cdr l2))))))



(eqlist? '(1 2 3) '(1 2 3))
(eqlist? '(1 2 3) '(1 2 3 4))
(eqlist? '() '())
(eqlist? '(green ((eggs) and) ham) '(green ((eggs) and) ham))
(eqlist? '(green ((eggs) and) ham) '(green ((eggs) and) spam))

(define (equal? s1 s2)
  ;; Are the two S-expressions s1 and s2 equal?
  ;; Reminder: an S-expression is either an atom or a (possible empty)
  ;;           list of S-expressions
  (cond ((and (atom? s1) (atom? s2)) (eq? s1 s2))
        ((or (atom? s1) (atom? s2)) #f)
        (else (eqlist? s1 s2))))

(define (eqlist? l1 l2)
  ;; Do lists l1 and l2 contain all the same elements at all the same depths?
  (cond ((and (null? l1) (null? l2)) #t)
        ((or (null? l1) (null? l2))  #f)
        ;; neither l1 nor l2 are null
        (else (and (equal? (car l1) (car l2))
                   (eqlist? (cdr l1) (cdr l2))))))

;; Note that these are mutually recursive!  Nice :)
