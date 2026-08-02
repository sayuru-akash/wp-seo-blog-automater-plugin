#!/usr/bin/env node
/* Verify the retry queue keeps its existing progress notice in the DOM. */
"use strict";

const fs = require( "fs" );
const path = require( "path" );

const source = fs.readFileSync( path.join( __dirname, "../admin/js/media-alt-text.js" ), "utf8" );

function assert( condition, message ) {
	if ( ! condition ) {
		console.error( "[FAIL] " + message );
		process.exit( 1 );
	}
}

assert( source.includes( "function resetProgressBox" ), "Retry flow must reset an existing progress notice." );
assert( source.includes( "createProgressBox( options.$host, options.$progressBox )" ), "Queue must accept a reusable progress notice." );
assert( source.includes( "$progressBox: $box" ), "Retry must pass the existing progress notice to the next queue." );
assert( ! source.includes( "$host: $box" ), "Retry must not use a removed progress notice as an insertion host." );

console.log( "[OK] Media image-text retry queue reuses its visible progress notice." );
