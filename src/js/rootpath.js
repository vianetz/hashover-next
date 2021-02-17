HashOverConstructor.rootPath = (function () {
    const scriptSrc = new URL(document.currentScript.src);
    return scriptSrc.origin;
})();
