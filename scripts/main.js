;(() => {
  'use strict'
  window.addEventListener(
    'load',
    () => {
      // Fetch all the forms we want to apply custom Bootstrap validation styles to
      let forms = document.getElementsByClassName('needs-validation')
      // Loop over them and prevent submission
      let validation = Array.prototype.filter.call(forms, form => {
        form.addEventListener(
          'submit',
          event => {
            if (form.checkValidity() === false) {
              event.preventDefault()
              event.stopPropagation()
            }
            form.classList.add('was-validated')
          },
          false
        )
      })
    },
    false
  )
})()

$(document).ready(() => {
  // DOM is fully loaded
  // Capitalize the first letter of First Name
  $('#firstName').on('change', function(e) {
    let $this = $(this),
      val = $this.val()
    RegExp = /\b[a-z]/g

    val = val.charAt(0).toUpperCase() + val.substr(1)
  })

  // Capitalize the first letter of Last Name
  $('#lastName').on('change', function(e) {
    let $this = $(this),
      val = $this.val()
    RegExp = /\b[a-z]/g

    val = val.charAt(0).toUpperCase() + val.substr(1)
  })

  // change the email to lowercase
  $('#email').on('change', function(e) {
    let $this = $(this),
      val = $this.val()
    val = val.toLowerCase()
  })

  $('form').submit(event => {
    // stop the button from submitting
    event.preventDefault()

    // Make the submit button load
    $('button').removeClass('btn-danger')
    $('button').toggleClass('btn-primary')
    $('button').html(
      'Loading <span class="spinner"></span><i class="fa fa-spinner fa-spin"></i></span>'
    )

    if (form.checkValidity() === true) {
      // put form data into variables
      let firstName = $.trim(document.getElementById('firstName').value)
      let lastName = $.trim(document.getElementById('lastName').value)
      let email = $.trim(document.getElementById('email').value)
      let phone = $.trim(document.getElementById('phone').value)
      let country = document.getElementById('country').value
      let occupation = $.trim(document.getElementById('occupation').value)
      let organisation = $.trim(document.getElementById('organisation').value)
      let member = document.querySelector('input[name="member"]:checked').value
      let referrer = $.trim(document.getElementById('referrer').value)
      let firstConference = document.querySelector(
        'input[name="firstConference"]:checked'
      ).value
      var currency = 'NGN'
      var amount = 36200 * 100
      if (country !== 'Nigeria') {
        var currency = 'USD'
      }
      if (country === 'Rwanda' && currency === 'USD') {
        var amount = 100 * 100
      } else if (currency === 'USD') {
        var amount = 300 * 100
      }
      // let amount = document.getElementById('amount').value * 100;

      let postData = JSON.stringify({
        firstName: firstName,
        lastName: lastName,
        email: email,
        phone: phone,
        country: country,
        occupation: occupation,
        organisation: organisation,
        member: member,
        referrer: referrer,
        firstConference: firstConference,
        currency: currency,
        amount: amount
      })

      fetch('scripts/paynow.php', {
        method: 'post',
        mode: 'same-origin',
        credentials: 'same-origin',
        body: postData
      })
        .then(response => {
          return response.json()
        })
        .then(data => {
          if (data === 'user_exists') {
            swal(
              'Already Registered',
              'You have already registered for the conference.',
              'error'
            )
            setTimeout(function() {
              window.location = 'https://awlo.org/awlc/'
            }, 3000)
          } else {
            window.location.href = data
            // console.log(data);
          }
        })
        .catch(error => {
          console.log('The Request Failed', error)
        })
    }
  })
})
