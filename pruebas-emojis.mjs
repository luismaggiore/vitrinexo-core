/**
 * Que no haya emojis ni pictogramas en nada que se lea.
 *
 * La marca los prohíbe en todas partes, y el plugin es donde más había: los
 * correos y el panel estaban llenos. Se mide sobre el copy y no sobre el
 * archivo entero, porque un → dentro de un comentario de código no lo ve
 * nadie, y prohibirlo ahí solo enseña a ignorar la prueba.
 *
 * No hay suite todavía en este repositorio, así que esta comprobación corre
 * sola y sin dependencias:  node pruebas-emojis.mjs
 */

import { readdirSync, readFileSync, statSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { join, relative } from 'node:path';

const RAIZ = fileURLToPath( new URL( '.', import.meta.url ) );
const PICTOGRAMAS = /[\u{1F000}-\u{1FAFF}\u{2190}-\u{21FF}\u{2300}-\u{23FF}\u{2600}-\u{27BF}\u{2B00}-\u{2BFF}\u{FE0F}]/u;
const esComentario = ( l ) => /^\s*(\/\/|\*|\/\*|#)/.test( l );
const FUERA = [ '.git', 'node_modules' ];

function archivos( carpeta = '' ) {
  const salida = [];
  for ( const nombre of readdirSync( join( RAIZ, carpeta ) ) ) {
    if ( FUERA.includes( nombre ) ) continue;
    const ruta = join( carpeta, nombre );
    if ( statSync( join( RAIZ, ruta ) ).isDirectory() ) salida.push( ...archivos( ruta ) );
    else if ( /\.(php|js)$/.test( nombre ) ) salida.push( ruta );
  }
  return salida;
}

const fallos = [];
for ( const archivo of archivos() ) {
  readFileSync( join( RAIZ, archivo ), 'utf8' ).split( '\n' ).forEach( ( linea, i ) => {
    if ( esComentario( linea ) ) return;
    const m = linea.match( PICTOGRAMAS );
    if ( m ) fallos.push( `${ relative( '.', archivo ) }:${ i + 1 } usa «${ m[ 0 ] }»: ${ linea.trim().slice( 0, 60 ) }` );
  } );
}

if ( fallos.length ) {
  console.log( '  ✕ emojis' );
  for ( const f of fallos ) console.log( `      ${ f }` );
  process.exit( 1 );
}
console.log( '  ✓ emojis' );
