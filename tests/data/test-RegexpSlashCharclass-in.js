console.log('heya!');    const regexWithSlashInIt = /[+\-*/],/;
console.log(
    'This here is all considered part of a regex starting with the last slash'  ,
    'So the whitespace is not compacted until here: /', // and now this is   considered to be part of a   string starting with single quote   that   single quote  earlier, so this one-line-comment won't be   stripped until   the apostrophe.
    // now all should be fine again but everything commented behind the double-slash of the previous comment that was taken to be part of a single quote string.
);     console.log('This is still commented out.');
