;; Hoxie Ackerman
;; The Little Schemer
;; Chapter 1: Toys

(define (atom? x) 
  (and (not (pair? x)) (not (null? x))))

;; Useful functions:
;; atom?
;; car
;; cdr
;; null?
;; eq?
;; 
