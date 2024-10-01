  <script src="{{ URL::asset('assets/plugins/datatable/js/jquery.dataTables.js') }}"></script>

  <script>
      /**
       * THIS ACTION HANDLER IS TO HANDLE THE SUBMIT BUTTON OF DELETE MODULE
       */
      $(document).ready(function() {
          /**
           * DISABLE SUBMIT BUTTON WHEN NOT INSERT ALL THE REQUIRED INPUT FIELDS
           */
          //Run validation on keyup and change events
          $('input[required]').on('keyup change', function() {
              validateForm('#mailboxForm', '.update-admin-menu');
          });

          // Initial validation check
          validateForm('#mailboxForm', '.update-admin-menu');

          /**
           * EVENT HANDLERS FOR SUBMIT AND DELETE ACTIONS
           */
          $('.force-delete-module').on('click', function(event) {
              event.preventDefault();
              var moduleId = $(this).data('id'); // Get the module ID from the data-id attribute
              var url = '{{ route('force-delete', ':id') }}'.replace(':id',
                  moduleId); // Replace :id with the actual module ID

              // Confirm deletion
              $.ajax({
                  type: 'DELETE',
                  url: url, // The route with the dynamic module ID
                  data: {
                      _method: 'DELETE', // Simulate DELETE method
                      _token: '{{ csrf_token() }}' // Include CSRF token
                  },
                  success: function(response) {
                      if (response.data == null) {
                          toastr.error(response.message, "Error");
                      } else {
                          if (response.data.menu_type === 'admin')
                              // descrease the counter
                              decreaseCounter('addMenuLabel');
                          else
                              decreaseCounter('FrontForm');
                          // return success message
                          toastr.success(response.message, "Success");
                          $('#' + response.data.menu_type + "-" + response.data.id)
                              .hide();

                      }

                  },
                  error: function(xhr) {
                      toastr.error(response, "Error");
                      // alert('An error occurred. Please try again.');
                  }
              });

          });

          $('#mailboxForm').on('submit', function(event) {
              event.preventDefault();
              var form = $(this);
              var url = form.attr('action'); // Get the form action URL
              var formData = form.serialize();

              $.ajaxSetup({
                  headers: {
                      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                      'Accept': 'application/json'
                  }
              });

              $.ajax({
                  url: url,
                  method: 'POST',
                  data: formData,
                  success: function(response) {

                      if (response.status == true) {
                          console.log(response.data.menu_type);
                          //$('#' + response.data.menu_type + "-" + response.data.id).hide();
                          $('#' + response.data.menu_type + "-" + response.data.id).find(
                              '.dd-handle').text(response.data.name);
                          toastr.success(response.message, "Success");
                      } else
                          toastr.error(response.message, "Error");
                  },
                  error: function(xhr, status, error) {
                      toastr.error(error, "Error");
                  }
              });

          });


      });


      /**
       * THIS FUNCTION IS TO DECREASE THE COUNTER OF A SPECIFC MODULE
       * @argument
       * */
      function decreaseCounter(moduleType) {
          var currentValue = parseInt($('#' + moduleType + "-counter").text(), 10);
          var newValue = currentValue - 1;
          $('#' + moduleType + "-counter").text(newValue);
      }
  </script>
