(function() {
    var appendInput = function appendInput() {
        var input = document.createElement("input"),
            br = document.createElement("br"),
            div = document.querySelector("form > div");
        input.type = "file";
        input.name = "files[]";
        div.appendChild(br);
        div.appendChild(input);
    }
    document.querySelector("input[type=button]").addEventListener("click", appendInput);
})();
