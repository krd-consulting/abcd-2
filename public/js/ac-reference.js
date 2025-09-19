$(document).ready(function () {
    // Initialize single-select autocomplete
    const $singleReferenceFields = $(".reference:not(.multiselect)");

    if ($singleReferenceFields.length > 0) {
        $singleReferenceFields.each(function () {
            const $field = $(this); // Cache `this` for clarity
            $field.autocomplete({
                source: function (request, response) {
                    $field.removeClass("ui-autocomplete-loading");
                    $.ajax({
                        url: "/dash/autocomplete",
                        dataType: "json",
                        data: {
                            term: request.term,
                            type: $field.data("reftype"),
                            form: $field.data("refform"),
                            field: $field.data("reffield"),
                        },
                        success: function (data) {
                            $field.removeClass("ui-autocomplete-loading");
                            response(data);
                        },
                    });
                },
                focus: function (event, ui) {
                    $field.val(ui.item.label).removeClass("ui-state-error");
                    $("span#ui-error").hide();
                    return false;
                },
                select: function (event, ui) {
                    $field.val(ui.item.label);
                    return false;
                },
                change: function (event, ui) {
                    if (!ui.item || $field.val() === "No valid matches found") {
                        $field.val("").addClass("ui-state-error");
                        $("span#ui-error").show();
                    }
                },
                minLength: 0,
                delay: 100,
            });

            // Customize the rendering of autocomplete items
            const autocompleteData = $field.data("ui-autocomplete") || $field.data("autocomplete");
            if (autocompleteData) {
                autocompleteData._renderItem = function (ul, item) {
                    var extra = item.extra ? "<br>" + item.extra : "";
                    return $("<li></li>")
                        .data("item.autocomplete", item)
                        .append("<a>" + item.label + extra + "</a>")
                        .appendTo(ul);
                };
            }

            // Add error message span
            $field.parents("li").append(
                "<span id='ui-error' class='ui-state-highlight hidden'>Please select one of the auto-suggested names.</span>"
            );
        });
    } else {
        console.warn("No single-select reference fields found to initialize.");
    }

    // Initialize multi-select autocomplete
    $(".reference.multiselect").each(function () {
        const $input = $(this);
        const $container = $input.parent();

        $input.autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: "/dash/autocomplete",
                    dataType: "json",
                    data: {
                        term: request.term,
                        type: $input.data("reftype"),
                        form: $input.data("refform"),
                        field: $input.data("reffield"),
                    },
                    success: function (data) {
                        response(
                            $.map(data, function (item) {
                                return {
                                    label: item.label,
                                    value: item.value,
                                };
                            })
                        );
                    },
                });
            },
            focus: function (event, ui) {
                event.preventDefault();
            },
            select: function (event, ui) {
                event.preventDefault();
                const $tag = $(
                    '<span class="tag-for-multi">' +
                        ui.item.label +
                        '<span class="remove" style="margin-left: 5px; cursor: pointer;">x</span></span>'
                );
                $tag.insertBefore($input);
                $tag.find(".remove").click(function () {
                    $(this).parent().remove();
                    adjustInputWidth();
                });
                $input.val("");
                adjustInputWidth();
                $input.autocomplete("close");
            },
            minLength: 0,
            delay: 100,
        });

        function adjustInputWidth() {
            const totalWidth = $container.width();
            let usedWidth = 0;
            $container.children(".tag").each(function () {
                usedWidth += $(this).outerWidth(true) + 10;
            });

            const availableWidth = Math.max(totalWidth - usedWidth - 20, 120);
            $input.css("width", availableWidth + "px");
        }

        adjustInputWidth();
        $(window).resize(adjustInputWidth);
    });
});

