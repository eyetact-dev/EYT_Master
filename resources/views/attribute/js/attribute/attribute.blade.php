  <script src="{{ URL::asset('assets/js/common/commonMethods.js') }}"></script>
  <script src="{{ URL::asset('assets/plugins/datatable/js/jquery.dataTables.js') }}"></script>
  <script>
      //1. pass the php variables into js
      //  ---- Admin ----
      var updateRoute;
      var storeRoute;
      var attributeId = @json($attribute->id);

      @if ($attribute->id != null)
          {
              updateRoute = @json(route('attribute.update', ['attribute' => $attribute->id]));

          }
      @else
          {
              storeRoute = @json(route('attribute.store'));
          }
      @endif

      /**
       * DISABLE CREATE ATTRIBUTE UNTIL ENTER ALL THE REQUIRED INPUT FIELD
       * */
      $("#role_form_modal").on('shown.bs.modal', function() {
          // Run validation on keyup and change events
          $('input[required]').on('keyup change', function() {
              validateForm('.create-attribute-form', '.create-attribute-form-submit');
          });

          $('select[required]').on('change', function() {
              console.log($(this).val());
              validateForm('.create-attribute-form', '.create-attribute-form-submit');

          });
          // Initial validation check
          validateForm('.create-attribute-form', '.create-attribute-form-submit');

      });

      $("#role_form_modal").on('shown.bs.modal', function() {
          // Run validation on keyup and change events
          $('input[required]').on('keyup change', function() {
              validateForm('#attributeCreate', '.create-attribute-form-submit');
          });


          // Initial validation check
          validateForm('#attributeCreate', '.create-attribute-form-submit');

      });

      $(document).ready(function() {

          $(document).on('change', "select[name='input_types']", function() {
              validateForm('#attributeCreate', '.create-attribute-form-submit');
          });

          // Validate dynamically added fields as well
          $(document).on('input', '#attributeCreate input', function() {
              validateForm('#attributeCreate', '.create-attribute-form-submit');
          });
          /**
           * THIS ACTION HANDLER IS TO HANDLE THE SUBMIT BUTTON OF CREATE ATTRIBUTE FORM
           */
          $('#attributeCreate').submit(function(e) {
              e.preventDefault(); // Prevent default form submission
              //compare the conditions to set the desired url(update | create new)
              //var attributeId = attributeId;
              // This comes from the Blade template
              var url = attributeId == null ? storeRoute : updateRoute;

              var formData = new FormData(this);
              // Setup CSRF token header
              $.ajaxSetup({
                  headers: {
                      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                      'Accept': 'application/json'
                  }
              });

              //AJAX request
              $.ajax({
                  type: 'POST',
                  url: url,
                  data: formData,
                  processData: false,
                  contentType: false,
                  dataType: 'json',

                  success: function(response) {
                      if (response.status === true) {

                          manageMessageResponse("role_form_modal", response,
                              "success", 3000);
                          $("#attributeCreate")[0].reset();
                          $('.form-control').removeClass('is-valid');
                          $('.form-control').removeClass('is-invalid');
                          table.ajax.reload();
                      } else {
                          manageMessageResponse("role_form_modal", response,
                              "danger",
                              3000);
                          $("#attributeCreate")[0].reset();

                          $('.form-control').removeClass('is-valid');
                          $('.form-control').removeClass('is-invalid');
                      }
                  },
                  error: function(xhr, status, error) {
                      var response = JSON.parse(xhr.responseText);
                      if (xhr.status === 422) {
                          var errors = xhr.responseJSON.errors;
                          displayValidationErrorsFields(
                              errors);
                          $("#attributeCreate")[0].reset();

                          $('.form-control').removeClass('is-valid');
                          $('.form-control').removeClass('is-invalid');
                      } else {


                          manageMessageResponse("role_form_modal", response.message,
                              "danger", 3000);
                          $("#attributeCreate")[0].reset();

                          $('.form-control').removeClass('is-valid');
                          $(
                              '.form-control').removeClass('is-invalid');
                      }
                  }
              });


          });


      });
  </script>
