-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 11-12-2025 a las 03:43:35
-- Versión del servidor: 11.4.8-MariaDB-cll-lve
-- Versión de PHP: 8.3.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `lgunprmiuy_PrismaticFlames`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `autores`
--

CREATE TABLE `autores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `biografia` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `autores`
--

INSERT INTO `autores` (`id`, `nombre`, `biografia`) VALUES
(101001, 'Brandon Sanderson', 'Brandon Sanderson (Lincoln, Nebraska, 19 de diciembre de 1975) es un escritor estadounidense de literatura fantástica y ciencia ficción. Es conocido sobre todo por el universo ficticio de Cosmere, en el que se ambientan la mayoría de sus novelas de fantasía, entre las que destacan las series Nacidos de la bruma (Mistborn) y El archivo de las tormentas.'),
(101002, 'Robert Jordan', 'James Oliver Rigney, Jr. (Charleston, Carolina del Sur; 17 de octubre de 1948-ibídem, 16 de septiembre de 2007), más conocido por el seudónimo Robert Jordan, fue un escritor estadounidense, famoso por ser autor de la saga de fantasía La rueda del tiempo.'),
(101003, 'Rebecca Yarros', 'Rebecca Yarros (Washington D. C. en 1981), es una escritora estadounidense especializada en fantasía romántica y romance contemporáneo. Su estilo apasionado, emocional y cargado de acción la ha convertido en una de las autoras más leídas del momento. Su éxito llegó con la saga Empíreo, iniciada con Alas de sangre (Fourth Wing), seguida por Alas de hierro (Iron Flame) y Alas de ónix (Onyx Storm), una trilogía ambientada en una academia militar con dragones, magia y relaciones intensas que ha conquistado a lectores de todo el mundo.'),
(101004, 'Marjane Satrapi', 'Marjane Satrapi (Rasht, Irán, 22 de noviembre de 1969) es una autora, ilustradora y cineasta franco-iraní, famosa por su novela gráfica Persépolis, donde narra su infancia y juventud durante la Revolución Islámica. Satrapi emigró a Francia en su adolescencia, donde desarrolló su carrera artística. Además de Persépolis, destacan Pollo con ciruelas y Bordados entre sus obras más conocidas. Ha recibido premios como el Premio del Jurado en el Festival de Cannes por la adaptación cinematográfica de Persépolis. Satrapi es celebrada por su estilo visual y su capacidad para abordar temas políticos y personales con honestidad y humor.'),
(101005, 'Sarah J. Maas', 'Sarah J. Maas (Estados Unidos, 1986) es una autora de fantasía nacida en Nueva York. Es conocida por la serie Throne of Glass, iniciada cuando tenía dieciséis años y publicada por Bloomsbury en 2012, así como por A Court of Thorns and Roses y Crescent City. Sus novelas han sido traducidas a decenas de idiomas y han figurado en las listas de los más vendidos del New York Times. Maas es reconocida por su capacidad de crear mundos complejos y personajes memorables, y ha recibido numerosos reconocimientos por su contribución al género de fantasía juvenil.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carritos`
--

CREATE TABLE `carritos` (
  `id` bigint(20) NOT NULL,
  `usuario_id` bigint(20) NOT NULL,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `carritos`
--

INSERT INTO `carritos` (`id`, `usuario_id`, `fecha_actualizacion`) VALUES
(403000001, 2030000004, '2025-12-09 06:01:17');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carrito_items`
--

CREATE TABLE `carrito_items` (
  `id` bigint(20) NOT NULL,
  `carrito_id` bigint(20) NOT NULL,
  `libro_id` bigint(20) NOT NULL,
  `cantidad` int(11) NOT NULL CHECK (`cantidad` > 0),
  `precio_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `carrito_items`
--

INSERT INTO `carrito_items` (`id`, `carrito_id`, `libro_id`, `cantidad`, `precio_unitario`) VALUES
(4, 403000001, 103001, 1, 535.00),
(5, 403000001, 103002, 1, 580.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `descripcion`) VALUES
(102001, 'Fantasía', 'Un tipo de ficción que incluye elementos sobrenaturales, mágicos, fantásticos o extraordinarios, como criaturas inexistentes o mundos diferentes.'),
(102002, 'Novela Gráfica', 'Una novela gráfica es un formato de historieta que, a diferencia del cómic tradicional, presenta una narrativa completa y autónoma, similar a un libro, con una estructura literaria más compleja y una mayor extensión'),
(102003, 'Novela Rosa', 'Una novela romántica es un género de ficción que se centra principalmente en la relación y el amor romántico entre dos personas, generalmente con un final emocionalmente satisfactorio y optimista .'),
(102004, 'Biografía', 'Una biografía es la historia de la vida de una persona, narrada desde su nacimiento hasta su muerte o hasta el presente si aún está viva, incluyendo logros, dificultades y momentos importantes');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `libros`
--

CREATE TABLE `libros` (
  `id` bigint(20) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `fecha_publicacion` date DEFAULT NULL,
  `portada_url` varchar(500) DEFAULT NULL,
  `es_proximo_lanzamiento` tinyint(1) NOT NULL DEFAULT 0,
  `estado` enum('disponible','agotado','oculto') DEFAULT 'disponible',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `libros`
--

INSERT INTO `libros` (`id`, `titulo`, `isbn`, `descripcion`, `precio`, `stock`, `fecha_publicacion`, `portada_url`, `es_proximo_lanzamiento`, `estado`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(103001, 'La Rueda del Tiempo 1. El ojo del mundo', '9788445007006', 'La vida de Rand Al’Thor y sus amigos en Campo de Emond ha resultado bastante monótona hasta que una joven misteriosa llega al pueblo. Moraine, una maga capaz de encauzar el Poder Único, anuncia el despertar de una terrible amenaza.\r\n\r\nEsa misma noche, el pueblo se ve atacado por espantosos trollocs sedientos de sangre, unas bestias semihumanas que hasta entonces se habían considerado una leyenda. Mientras Campo de Emond soporta la ofensiva, Moraine y su guardián ayudan a Rand y a sus amigos a escapar.', 535.00, 12, '2021-11-12', 'https://prismaticflames.space/images/books/RuedaDelTiempoElOjoDelMundo.jpg', 12, 'disponible', '2025-12-05 06:44:20', '2025-12-09 05:10:24'),
(103002, 'Nacidos de la bruma. El imperio final', '9788417347291', 'Durante mil años han caído las cenizas y nada florece. Durante mil años los skaa han sido esclavizados y viven sumidos en un miedo inevitable. Durante mil años el Lord Legislador reina con un poder absoluto gracias al terror, a sus poderes y a su inmortalidad. Le ayudan «obligadores e «inquisidores , junto a la poderosa magia de la alomancia. Pero los nobles a menudo han tenido trato sexual con jóvenes skaa y, aunque la ley lo prohíbe, algunos de sus bastardos han sobrevivido y heredado los poderes alománticos: son los «nacidos de la bruma (mistborn). Ahora, Kelsier, el «superviviente , el único que ha logrado huir de los Pozos de Hathsin, ha encontrado a Vin, una pobre chica skaa con mucha suerte... Tal vez los dos, unidos a la rebelión que los skaa intentan desde hace mil años, logren cambiar el mundo y la atroz dominación del Lord Legislador.', 580.00, 10, '2020-01-21', 'https://prismaticflames.space/images/books/ImperioFinal.jpg', 0, 'disponible', '2025-12-05 06:44:20', '2025-12-11 08:11:48');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `libros_autores`
--

CREATE TABLE `libros_autores` (
  `libro_id` bigint(20) NOT NULL,
  `autor_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `libros_autores`
--

INSERT INTO `libros_autores` (`libro_id`, `autor_id`) VALUES
(103002, 101001),
(103001, 101002);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `libros_categorias`
--

CREATE TABLE `libros_categorias` (
  `libro_id` bigint(20) NOT NULL,
  `categoria_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `libros_categorias`
--

INSERT INTO `libros_categorias` (`libro_id`, `categoria_id`) VALUES
(103001, 102001),
(103002, 102001);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id` bigint(20) NOT NULL,
  `usuario_id` bigint(20) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `puntos_obtenidos` int(11) NOT NULL DEFAULT 0,
  `estado` enum('pendiente','pagado','enviado','cancelado','devuelto') DEFAULT 'pendiente',
  `metodo_pago` varchar(50) DEFAULT NULL,
  `direccion_envio` varchar(255) DEFAULT NULL,
  `fecha_pedido` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id`, `usuario_id`, `total`, `puntos_obtenidos`, `estado`, `metodo_pago`, `direccion_envio`, `fecha_pedido`) VALUES
(2, 2030000004, 1650.00, 165, 'pendiente', 'tarjeta_credito', 'textotextotexto', '2025-12-11 07:11:47');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido_items`
--

CREATE TABLE `pedido_items` (
  `id` bigint(20) NOT NULL,
  `pedido_id` bigint(20) NOT NULL,
  `libro_id` bigint(20) NOT NULL,
  `cantidad` int(11) NOT NULL CHECK (`cantidad` > 0),
  `precio_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedido_items`
--

INSERT INTO `pedido_items` (`id`, `pedido_id`, `libro_id`, `cantidad`, `precio_unitario`) VALUES
(4040001, 2, 103001, 2, 535.00),
(4040002, 2, 103002, 1, 580.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`, `descripcion`) VALUES
(201001, 'Cliente', 'Usuario final de la aplicación, solo puede comprar y revisar.'),
(201002, 'Moderador', 'Encargado de mantener en orden la página'),
(201003, 'Administrador', 'Puede modificar los roles de los usuarios\r\n');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` bigint(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `estado` enum('activo','inactivo','bloqueado') DEFAULT 'activo',
  `puntos_acumulados` int(11) NOT NULL DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellido`, `email`, `password_hash`, `telefono`, `estado`, `puntos_acumulados`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(2030000001, '', '', '', '', NULL, 'activo', 0, '2025-12-05 16:11:22', '2025-12-05 16:12:02'),
(2030000002, 'Admin', 'Istrador', 'admin@spaceship.com', '$2y$10$0tmzGjd2bb1MG8fEb0Bn/OY3uhUszv4NrOUpLfVRTZy490wXEgb4m', '6311953960', 'activo', 0, '2025-12-05 16:18:06', '2025-12-05 16:18:06'),
(2030000003, 'test', 'ing', 'test@gmail.com', '$2y$10$n5eoajdXbkyFXPfldIayYeIwb4w8kN6MszKjvH/2TWPKonCXBOPBO', '6311234567', 'activo', 0, '2025-12-05 16:22:22', '2025-12-05 16:22:22'),
(2030000004, 'Alonso', 'Gaxiola Romero', 'alonsogaxiola03@gmail.com', '$2y$12$5Ge1p0x5nWHpL3Xw9a5H1OKyg.vB0JcIIKti.7RaAMC7lkoCnRRim', '+526311953960', 'activo', 165, '2025-12-05 17:34:11', '2025-12-11 07:13:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_roles`
--

CREATE TABLE `usuarios_roles` (
  `usuario_id` bigint(20) NOT NULL,
  `rol_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios_roles`
--

INSERT INTO `usuarios_roles` (`usuario_id`, `rol_id`) VALUES
(2030000003, 201001),
(2030000004, 201001),
(2030000001, 201002),
(2030000002, 201003);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `wishlist`
--

CREATE TABLE `wishlist` (
  `id` bigint(20) NOT NULL,
  `usuario_id` bigint(20) NOT NULL,
  `libro_id` bigint(20) NOT NULL,
  `fecha_agregado` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `wishlist`
--

INSERT INTO `wishlist` (`id`, `usuario_id`, `libro_id`, `fecha_agregado`) VALUES
(2, 2030000004, 103002, '2025-12-11 08:17:31');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `autores`
--
ALTER TABLE `autores`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `carritos`
--
ALTER TABLE `carritos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `carrito_items`
--
ALTER TABLE `carrito_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_carrito_item` (`carrito_id`,`libro_id`),
  ADD KEY `libro_id` (`libro_id`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `libros`
--
ALTER TABLE `libros`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `isbn` (`isbn`);

--
-- Indices de la tabla `libros_autores`
--
ALTER TABLE `libros_autores`
  ADD PRIMARY KEY (`libro_id`,`autor_id`),
  ADD KEY `autor_id` (`autor_id`);

--
-- Indices de la tabla `libros_categorias`
--
ALTER TABLE `libros_categorias`
  ADD PRIMARY KEY (`libro_id`,`categoria_id`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `pedido_items`
--
ALTER TABLE `pedido_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`),
  ADD KEY `libro_id` (`libro_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `usuarios_roles`
--
ALTER TABLE `usuarios_roles`
  ADD PRIMARY KEY (`usuario_id`,`rol_id`),
  ADD KEY `rol_id` (`rol_id`);

--
-- Indices de la tabla `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_wishlist` (`usuario_id`,`libro_id`),
  ADD KEY `libro_id` (`libro_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `autores`
--
ALTER TABLE `autores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101006;

--
-- AUTO_INCREMENT de la tabla `carritos`
--
ALTER TABLE `carritos`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=403000003;

--
-- AUTO_INCREMENT de la tabla `carrito_items`
--
ALTER TABLE `carrito_items`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102005;

--
-- AUTO_INCREMENT de la tabla `libros`
--
ALTER TABLE `libros`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103003;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `pedido_items`
--
ALTER TABLE `pedido_items`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4040003;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=201004;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2030000005;

--
-- AUTO_INCREMENT de la tabla `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `carritos`
--
ALTER TABLE `carritos`
  ADD CONSTRAINT `carritos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `carrito_items`
--
ALTER TABLE `carrito_items`
  ADD CONSTRAINT `carrito_items_ibfk_1` FOREIGN KEY (`carrito_id`) REFERENCES `carritos` (`id`),
  ADD CONSTRAINT `carrito_items_ibfk_2` FOREIGN KEY (`libro_id`) REFERENCES `libros` (`id`);

--
-- Filtros para la tabla `libros_autores`
--
ALTER TABLE `libros_autores`
  ADD CONSTRAINT `libros_autores_ibfk_1` FOREIGN KEY (`libro_id`) REFERENCES `libros` (`id`),
  ADD CONSTRAINT `libros_autores_ibfk_2` FOREIGN KEY (`autor_id`) REFERENCES `autores` (`id`);

--
-- Filtros para la tabla `libros_categorias`
--
ALTER TABLE `libros_categorias`
  ADD CONSTRAINT `libros_categorias_ibfk_1` FOREIGN KEY (`libro_id`) REFERENCES `libros` (`id`),
  ADD CONSTRAINT `libros_categorias_ibfk_2` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`);

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `pedido_items`
--
ALTER TABLE `pedido_items`
  ADD CONSTRAINT `pedido_items_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`),
  ADD CONSTRAINT `pedido_items_ibfk_2` FOREIGN KEY (`libro_id`) REFERENCES `libros` (`id`);

--
-- Filtros para la tabla `usuarios_roles`
--
ALTER TABLE `usuarios_roles`
  ADD CONSTRAINT `usuarios_roles_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `usuarios_roles_ibfk_2` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`);

--
-- Filtros para la tabla `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`libro_id`) REFERENCES `libros` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
