/* THIS FUNCTION IS TO CHECK EACH OF THE REQUIRED FIELDS IF THEY ARE FILLED 
           IN ORDER TO ENABLE THE SAVE BUTTON
        */
function validateForm(moduleForm, classFormSubmit) {
    let isValid = true;

    // Check if all required input fields have values
    $(moduleForm + " input[required]").each(function () {
        if ($(this).val() === "") {
            isValid = false;
        }
    });

    // Check if all required select fields have a valid, non-disabled option selected
    $(moduleForm + " select[required]").each(function () {
        let selectedValue = $(this).val(); // Get the selected value
        // Check if no value is selected, or if the selected option is disabled
        if (
            selectedValue === null ||
            selectedValue === "" ||
            $(this).find("option:selected").is(":disabled")
        ) {
            isValid = false;
        }
    });

    // Enable/disable the submit button based on the form's validity
    $(classFormSubmit).prop("disabled", !isValid);
    console.log(
        "Submit button disabled: " + $(classFormSubmit).prop("disabled")
    );
}
