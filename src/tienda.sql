CREATE EXTENSION IF NOT EXISTS pgcrypto;
CREATE EXTENSION IF NOT EXISTS unaccent;

DROP TABLE IF EXISTS articulos CASCADE;
CREATE TABLE articulos (
    id          bigserial     PRIMARY KEY,
    codigo      varchar(13)   NOT NULL UNIQUE,
    descripcion varchar(255)  NOT NULL,
    precio      numeric(7, 2) NOT NULL,
    stock       int           NOT NULL,
    categoria_id bigint       NOT NULL REFERENCES categorias (id),
    oferta_id   bigint       REFERENCES ofertas (id)
);

DROP TABLE IF EXISTS categorias CASCADE;
CREATE TABLE categorias (
    id          bigserial PRIMARY KEY,
    categoria   varchar(255) UNIQUE NOT NULL
);

DROP TABLE IF EXISTS ofertas CASCADE;
CREATE TABLE ofertas (
    id              bigserial PRIMARY KEY,
    fecha_inicio    date    NOT NULL,
    fecha_fin       date    NOT NULL,
    descuento       DECIMAL(10,2)  NOT NULL,
    tipo_descuento  varchar(50) NOT NULL
);

DROP TABLE IF EXISTS etiquetas CASCADE;
CREATE TABLE etiquetas (
    id          bigserial PRIMARY KEY,
    etiqueta   text      NOT NULL UNIQUE
);

DROP TABLE IF EXISTS articulos_etiquetas CASCADE;
CREATE TABLE articulos_etiquetas (
    articulo_id bigint NOT NULL REFERENCES articulos (id),
    etiqueta_id bigint NOT NULL REFERENCES etiquetas (id),
    PRIMARY KEY (articulo_id, etiqueta_id)
);

DROP TABLE IF EXISTS valoraciones CASCADE;
CREATE TABLE valoraciones (
    articulo_id bigint  NOT NULL REFERENCES  articulos   (id),
    usuario_id  bigint  NOT NULL REFERENCES  usuarios    (id),
    valoracion  int     CHECK (valoracion >= 1 AND valoracion <= 5),
    PRIMARY KEY (articulo_id, usuario_id)
);

DROP TABLE IF EXISTS comentarios CASCADE;
CREATE TABLE comentarios (
    articulo_id bigint  NOT NULL REFERENCES  articulos   (id),
    usuario_id  bigint  NOT NULL REFERENCES  usuarios    (id),
    comentario  varchar(255),
    PRIMARY KEY (articulo_id, usuario_id)
);


DROP TABLE IF EXISTS usuarios CASCADE;
CREATE TABLE usuarios (
    id       bigserial    PRIMARY KEY,
    usuario  varchar(255) NOT NULL UNIQUE,
    password varchar(255) NOT NULL,
    validado   boolean      NOT NULL
);

DROP TABLE IF EXISTS facturas CASCADE;
CREATE TABLE facturas (
    id         bigserial  PRIMARY KEY,
    created_at timestamp  NOT NULL DEFAULT localtimestamp(0),
    usuario_id bigint NOT NULL REFERENCES usuarios (id),
    metodo_pago   varchar(50) NOT NULL,
    cupon_id    bigint       REFERENCES cupones(id)
);

DROP TABLE IF EXISTS articulos_facturas CASCADE;
CREATE TABLE articulos_facturas (
    articulo_id bigint NOT NULL REFERENCES articulos (id),
    factura_id  bigint NOT NULL REFERENCES facturas (id),
    cantidad    int    NOT NULL,
    PRIMARY KEY (articulo_id, factura_id)
);

DROP TABLE IF EXISTS comentarios_facturas CASCADE;
CREATE TABLE comentarios_facturas (
    fecha_creacion timestamp  NOT NULL DEFAULT localtimestamp(0),
    texto       varchar(255),
    usuario_id bigint NOT NULL REFERENCES usuarios (id),
    articulo_id bigint NOT NULL REFERENCES articulos(id),
    PRIMARY KEY (articulo_id, usuario_id)
);

DROP TABLE IF EXISTS cupones CASCADE;
CREATE TABLE cupones (
    id      bigserial  PRIMARY KEY,
    fecha_inicio      date NOT NULL,
    fecha_fin       date NOT NULL,
    cupon       varchar (25),
    descuento DECIMAL(10,2)   NOT NULL
);



-- Carga inicial de datos de prueba:

INSERT INTO articulos (codigo, descripcion, precio, stock, categoria_id, oferta_id)
    VALUES ('18273892389', 'Yogur piña', 2.50, 20, 2, null),
           ('83745828273', 'Tigretón', 1.10, 30, 2, null),
           ('51736128495', 'Disco duro SSD 500 GB', 150.30, 15, 1, null),
           ('51786128435', 'Disco duro M2 500 GB', 180.30, 0, 1, null),
           ('83745228673', 'Chandal', 30.10, 15, 3,1),
           ('51786198495', 'Traje', 250.30, 1, 3, 1);

INSERT INTO usuarios (usuario, password, validado)
    VALUES ('admin', crypt('admin', gen_salt('bf', 10)), true),
           ('pepe', crypt('pepe', gen_salt('bf', 10)), true),
           ('juan', crypt('juan', gen_salt('bf', 10)), false);

INSERT INTO categorias (categoria)
    VALUES ('Electrónica'),
            ('Alimentación'),
            ('Ropa'),
            ('Hogar');

INSERT INTO etiquetas (etiqueta)
    VALUES ('Electrónica'),
            ('Hogar'),
            ('Deporte'),
            ('Fruta'),
            ('Dulce'),
            ('Alimentación'),
            ('Ordenadores'),
            ('Ropa');

INSERT INTO articulos_etiquetas (articulo_id, etiqueta_id)
    VALUES (1, 6),
            (1, 4),
            (1, 5),
            (2, 6),
            (2, 5),
            (3, 7),
            (3, 1),
            (4, 7),
            (4, 1),
            (5, 3),
            (5, 8),
            (6, 8);

INSERT INTO cupones (fecha_inicio, fecha_fin, cupon, descuento) VALUES ('2023-05-16', '2023-12-31', 'DESCUENTO10', 10);

INSERT INTO ofertas (fecha_inicio, fecha_fin, descuento, tipo_descuento)
VALUES ('2023-06-01', '2023-06-15', 10, 'DESCUENTO10');
