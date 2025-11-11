if (/Mobi|Android/i.test(navigator.userAgent)) {
    document.getElementById("ubbc-sticky").parentElement.style.position = "fixed";
    document.getElementById("ubbc-sticky").parentElement.style.top = "0";
    document.getElementById("ubbc-sticky").parentElement.style.width = "100%";
    document.getElementById("ubbc-sticky").parentElement.style.zIndex = "1000";
}