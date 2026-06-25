UPDATE `escuela_san_martin`.`users`
SET `password` = '$2y$12$tLrwiiNgLoFMVYJnzCwhHu/kflrXcfSjYdDH0vWGQXt1UWvzGDk/q',
    `activo` = 1
WHERE `email` = 'admin@eeso225.edu.ar';

UPDATE `kiosco`.`usuarios`
SET `password` = '$2y$12$j61krcvCIrhMDtFkfPHnnugD8TvRk5sXlMz3qSrRUcbXOrA4yFfPm',
    `activo` = 1,
    `es_admin` = 1,
    `super_admin` = 1
WHERE `email` = 'admin@escuela.com';
