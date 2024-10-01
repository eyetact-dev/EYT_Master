<style>
    #contactList {
        border: 1px solid #ccc;
        max-height: 150px;
        overflow-y: auto;
        position: absolute;
        background-color: white; /* Change to ensure visibility */
        width: calc(100% - 20px);
        z-index: 1000;
        /* display: block !important; */
    }

    #contactList li {
        padding: 10px;
        cursor: pointer;
        color: black;
    }

    #contactList li:hover {
        background-color: #f0f0f0;
    }
</style>

<form action="{{ route('users.store') }}" method="POST" id="mailboxForm" enctype="multipart/form-data">
    @csrf

    <input type="hidden" name="role" value="admin" />

    <input type="hidden" name="contact_id" id="contact_id" value="0" />

    <div class="row">

        <div class="col-lg-12 col-sm-12">
            <label class="form-label">avatar</label>
            <input type="file" class="dropify" name="avatar" data-default-file="" data-height="180" />
        </div>

        <div class="col-sm-6 col-md-6">
            <div class="input-box">
                <label for="name" class="input-label">Name</label>
                <input type="text" class="google-input name-input" name="name" id="name" value="" />

                <ul id="contactList" style="display:none;padding:0px;">


                </ul>

            </div>
            @error('name')
                <label id="name-error" class="error" for="name">{{ $message }}</label>
            @enderror
        </div>


        <div class="col-sm-6 col-md-6">
            <div class="input-box">
                <label for="username" class="input-label">username</label>
                <input type="text" class="google-input" name="username" id="username" value="" />
            </div>
            @error('username')
                <label id="username-error" class="error" for="username">{{ $message }}</label>
            @enderror
        </div>

        <div class="col-sm-6 col-md-6">
            <div class="input-box">
                <label for="email" class="input-label">email</label>
                <input type="email" class="google-input" name="email" id="email" value="" />
            </div>
            @error('email')
                <label id="email-error" class="error" for="email">{{ $message }}</label>
            @enderror
        </div>

        <div class="col-sm-6 col-md-6">
            <div class="input-box">
                <label for="phone" class="input-label">phone</label>
                <input type="text" class="google-input" name="phone" id="phone" value="" />
            </div>
            @error('phone')
                <label id="phone-error" class="error" for="phone">{{ $message }}</label>
            @enderror
        </div>

        <div class="col-sm-6 col-md-6">
            <div class="input-box">
                <label for="address" class="input-label">address</label>
                <input type="text" class="google-input" name="address" id="address" value="" />
            </div>
            @error('address')
                <label id="address-error" class="error" for="address">{{ $message }}</label>
            @enderror
        </div>

        <div class="col-sm-6 col-md-6">
            <div class="input-box">
                <label for="website" class="input-label">website</label>
                <input type="text" class="google-input" name="website" id="website" value="" />
            </div>
            @error('website')
                <label id="website-error" class="error" for="website">{{ $message }}</label>
            @enderror
        </div>

        <div class="col-sm-6 col-md-6">
            <div class="input-box">
                <label for="name" class="input-label">password</label>
                <input type="password" class="google-input" name="password" id="password" value="" />
            </div>
            @error('password')
                <label id="password-error" class="error" for="password">{{ $message }}</label>
            @enderror
        </div>
        <div class="col-sm-6 col-md-6">
            <div class="input-box">
                <select class=" google-input" name="group_id[]" tabindex="null" multiple>
                    <option selected disabled>Select Customer Group</option>
                    @foreach ($groups as $group)
                        <option value="{{ $group->id }}">{{ $group->id }} - {{ $group->name }}</option>
                    @endforeach
                </select>
            </div>
            @error('group_id')
                <label id="user_id-error" class="error" for="group_id">{{ $message }}</label>
            @enderror

        </div>




    </div>



    <div class="card-footer">
        <button class="btn btn-primary" type="submit">Save</button>
        <a href="{{ route('main_mailbox.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>



<script>

$(document).on('input', '#name', function() {

    let query = $(this).val();
  let list = $(this).closest('.input-box').find('#contactList');

  if (query.length > 0) {
      $.ajax({
          type: "GET",
          url: '{{ route('contacts.search') }}',
          data: { query: query },
          success: function(data) {
              list.empty();
              if (data.length > 0) {
                  data.forEach(function(contact) {
                      list.append('<li>' + contact.name + '</li>');
                  });
                  list.show();
              } else {
                  list.hide();
              }

              list.find('li').on('click', function() {

                  let selectedContact = data.find(c => c.name === $(this).text());
                  console.log(selectedContact)



                           let form = $(this).closest('form');


                          let nameField = form.find('#name');
                          let addressField = form.find('#address');
                          let phoneField = form.find('#phone');
                          let contactIdField = form.find('#contact_id');


                          nameField.val(selectedContact.name);
                          addressField.val(selectedContact.address);
                          phoneField.val(selectedContact.phone);
                          contactIdField.val(selectedContact.id);





                    //   console.log(nameField.val())
                    //   console.log(addressField.val())
                    //   console.log(phoneField.val())
                       console.log(contactIdField.val())

                  list.hide();
              });
          }
      });
  } else {
      list.hide();
  }
  });

</script>
