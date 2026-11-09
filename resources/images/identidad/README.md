# Logotipos oficiales de SICOP

Activos permanentes del frontend. Los componentes Vue los importan directamente; Vite publica copias con hash en el build. No dependen de TEMPORARY_RESOURCES en ejecución.

## Procedencia

Documento suministrado por el usuario:
`TEMPORARY_RESOURCES/diseno/identidad/MANUAL_DE_MARCA_SICOP.docx`

SHA-256 del DOCX:
`9575ea98570d8bd61c9b2a4b2ee06550adfe5f60e191ff0ab2634cd78e4a6c9d`

| Archivo | Parte original del DOCX | Dimensiones | Formato | SHA-256 |
| --- | --- | --- | --- | --- |
| sicop-circular.png | word/media/image1.png (rId5) | 1000 × 1000 | PNG RGBA; esquinas transparentes | 2213d03d659a53cda4bda844abe00ba1c33f3d2c9bc26c3eb8cbfdd0e199fe29 |
| sicop-horizontal.png | word/media/image2.png (rId6) | 1400 × 560 | PNG RGB; fondo crema original | e9a457e2f4079d9490501c950c43cf29567a59de414143851e42b969fe188764 |

Los archivos son idénticos byte por byte a las partes del DOCX. No se recortaron, recolorearon, reescalaron ni recodificaron; tampoco se vectorizaron. Se conservan los márgenes incluidos en los originales.

## Uso y calidad

Se revisaron las tres imágenes del manual, incluida `word/media/image3.png` (900 × 320), que fija un ancho mínimo recomendado de **120 px en digital / 2,5 cm en impresos**. La tercera imagen es una lámina de instrucciones, no otro logotipo.

- Circular: login y demás layouts de autenticación, 120 px en móvil, 128 px en tableta, 144 px en escritorio.
- Horizontal: navegación expandida, hasta 208 px de ancho y proporción 2,5:1.
- En el sidebar colapsado no se renderiza una miniatura de marca: se conserva el enlace existente con un icono de navegación de Lucide.
- Ambos originales tienen resolución suficiente incluso para estos tamaños a densidad 3×. No ampliar más allá de su resolución nativa.
- No aplicar filtros CSS, tintes, recortes, deformaciones ni cambiar el fondo incorporado.
- El manual no incluye una variante para favicon de 16/32 px; no crear una a partir de una reinterpretación.

Tipografía de interfaz: Montserrat SemiBold/Bold y Segoe UI; colores principales #153359, #ECC254 y #F3EEDD. El dibujo tipográfico del logo permanece dentro de la imagen original.
