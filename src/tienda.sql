DROP TABLE IF EXISTS articulos CASCADE;

CREATE TABLE articulos (
    id          bigserial     PRIMARY KEY,
    codigo      varchar(13)   NOT NULL UNIQUE,
    descripcion varchar(255)  NOT NULL,
    precio      numeric(7, 2) NOT NULL,
    stock       int           NOT NULL
);

DROP TABLE IF EXISTS categorias CASCADE;

CREATE TABLE categorias (
    id         bigserial      PRIMARY KEY,
    nombre     varchar(20)    NOT NULL UNIQUE
);

DROP TABLE IF EXISTS articulos_categorias CASCADE;

CREATE TABLE articulos_categorias (
    articulo_id bigint NOT NULL REFERENCES articulos (id),
    categoria_id  bigint NOT NULL REFERENCES categorias (id),
    PRIMARY KEY (articulo_id, categoria_id)
);


DROP TABLE IF EXISTS usuarios CASCADE;

CREATE TABLE usuarios (
    id       bigserial    PRIMARY KEY,
    usuario  varchar(255) NOT NULL UNIQUE,
    password varchar(255) NOT NULL,
    validado bool         NOT NULL
);

DROP TABLE IF EXISTS facturas CASCADE;

CREATE TABLE facturas (
    id         bigserial  PRIMARY KEY,
    created_at timestamp  NOT NULL DEFAULT localtimestamp(0),
    usuario_id bigint NOT NULL REFERENCES usuarios (id)
);

DROP TABLE IF EXISTS articulos_facturas CASCADE;

CREATE TABLE articulos_facturas (
    articulo_id bigint NOT NULL REFERENCES articulos (id),
    factura_id  bigint NOT NULL REFERENCES facturas (id),
    cantidad    int    NOT NULL,
    PRIMARY KEY (articulo_id, factura_id)
);

-- Carga inicial de datos de prueba:

INSERT INTO articulos (codigo, descripcion, precio, stock)
    VALUES ('18273892389', 'Yogur piña', 200.50, 4),
           ('83745828273', 'Tigretón', 50.10, 2),
           ('51736128495', 'Disco duro SSD 500 GB', 150.30, 0),
           ('83746828273', 'Tigretón', 50.10, 3),
           ('51786128435', 'Disco duro SSD 500 GB', 150.30, 5),
           ('83745228673', 'Tigretón', 50.10, 8),
           ('51786198495', 'Disco duro SSD 500 GB', 150.30, 1);

INSERT INTO usuarios (usuario, password, validado)
    VALUES ('admin', crypt('admin', gen_salt('bf', 10)), true),
           ('pepe', crypt('pepe', gen_salt('bf', 10)), false);

INSERT INTO categorias(nombre)
    VALUES ('tecnología'),
            ('alimentación'),
            ('lácteos');

INSERT INTO articulos_categorias (articulo_id, categoria_id)
    VALUES (1,2),(1,3),(2,2),(3,1),(4,2),(5,1),(6,2),(7,1);