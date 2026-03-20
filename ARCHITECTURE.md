# Architecture: magento-zend-pdf

## Purpose

Magento's fork of Zend Framework 1's PDF generation library. Provides programmatic creation and modification of PDF documents — pages, text, images, fonts, annotations, outlines, and actions — without external dependencies.

## Directory Structure

```
library/Zend/Pdf/
  Pdf.php                  — Root document: pages, metadata, save/render, cross-reference table
  Page.php                 — Canvas for drawing: text, shapes, images, transformations
  Canvas/Interface.php     — Drawing operations interface
  Font.php                 — Font loading and metrics
  Image.php                — Image loading (PNG, JPEG, TIFF)
  Color/                   — Color space models: RGB, CMYK, Grayscale, HTML named colors
  Element/                 — PDF data types: Boolean, Numeric, String, Name, Array, Dictionary, Stream, Reference
  Filter/                  — Stream decoders: FlateDecode, LZWDecode, ASCII85, RunLength
  Resource/
    Font/                  — Font subtypes: Type1, TrueType, OpenType, CID, extracted
    Image/                 — PNG, JPEG, TIFF image resources
    ContentStream.php      — PDF content stream (drawing instructions)
  FileParser/              — Binary parsers for font files (OpenType/TrueType) and images
  Action/                  — PDF actions: GoTo, URI, JavaScript, SubmitForm, ResetForm, etc.
  Destination/             — PDF navigation destinations: Fit, FitH, FitV, Zoom, Named
  Annotation/              — Page annotations: Link, Text, Markup, FileAttachment
  Outline/                 — Bookmark outlines (table of contents)
  Cmap/                    — Character maps for font encoding
  Parser.php               — Binary PDF parser: loads existing PDFs
  Element_Factory.php      — Creates and manages indirect PDF objects with cross-reference tracking
```

## Key Design Decisions

- **Object graph model**: The document is an object graph of `Element` subclasses mirroring the PDF specification's structure; serialization to bytes is a separate step
- **Cross-reference table management**: `ElementFactory` tracks all indirect objects and assigns object IDs; the factory is cloned per-document to avoid ID conflicts when merging pages from different PDFs
- **Page as canvas**: `Page` implements `Canvas_Interface` with a drawing instruction buffer; instructions are flushed to the document's content stream on save
- **No external dependencies**: All PDF primitives, font parsing (TrueType/OpenType), image decoding, and stream compression/decompression are implemented in pure PHP

## Extension Points

- Add a new filter/decoder by implementing `Filter_Interface`
- Load custom fonts via `Zend_Pdf_Font::fontWithPath($path)`

## Dependency Flow

```
Zend_Pdf::load($file) or new Zend_Pdf()
  → Parser (binary parse) / ElementFactory (new doc)
  → Page[] (each Page has a Canvas instruction list)
  → Font / Image resources attached to pages
  → Zend_Pdf::render() → binary PDF output
```
