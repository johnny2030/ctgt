$( document ).ready( function () {
	$( "#loginForm" ).validate( {
		rules: {
			username: {
				required: true,
				minlength: 2
			},
			password: {
				required: true,
				minlength: 5
			}
		},
		messages: {
			username: {
				required: "Please enter a username",
				minlength: "Your username must consist of at least 2 characters"
			},
			password: {
				required: "Please provide a password",
				minlength: "Your password must be at least 5 characters long"
			}
		},
		errorElement: "em",
		errorPlacement: function ( error, element ) {
			// Add the `help-block` class to the error element
			error.addClass( "help-block" );

			// Add `has-feedback` class to the parent div.form-group
			// in order to add icons to inputs
			element.parents( ".col-sm-6" ).addClass( "has-feedback" );

			if ( element.prop( "type" ) === "checkbox" ) {
				error.insertAfter( element.parent( "label" ) );
			} else {
				error.insertAfter( element );
			}

		},
		highlight: function ( element, errorClass, validClass ) {
			$( element ).parents( ".col-sm-6" ).addClass( "has-error" ).removeClass( "has-success" );
			$( element ).next( "span" ).addClass( "glyphicon-remove" ).removeClass( "glyphicon-ok" );
		},
		unhighlight: function ( element, errorClass, validClass ) {
			$( element ).parents( ".col-sm-6" ).addClass( "has-success" ).removeClass( "has-error" );
			$( element ).next( "span" ).addClass( "glyphicon-ok" ).removeClass( "glyphicon-remove" );
		}
	} );

	$( "#uploadForm" ).validate( {
		rules: {
			name: {
				required: true
			},
			birth_of_date: {
				required: true
			},
			gender: {
				required: true
			},
			native_place: {
				required: true
			},
			grade: {
				required: true
			},
			speciality: {
				required: true
			},
			
			phone: {
				required: true
			},
			email: {
				required: true
			},
			home_address: {
				required: true
			},
			present_address: {
				required: true
			},
			counselor: {
				required: true
			},
			counselor_phone: {
				required: true
			},
			counselor_email: {
				required: true
			},
			avatar: {
				required: true
			},
			wakeup_time: {
				required: true
			},
			bedtime: {
				required: true
			},
			smoking_status: {
				required: true
			},
			frequency_of_cleaning: {
				required: true
			},
			hobby: {
				required: true
			},
			reason: {
				required: true
			}
		},
		messages: {
			name: {
				required: "Please enter a name"
			},
			birth_of_date: {
				required: "Please enter a birth of date"
			},
			gender: {
				required: "Please select a gender"
			},
			native_place: {
				required: "Please enter a native place"
			},
			grade: {
				required: "Please enter a grade"
			},
			speciality: {
				required: "Please enter a speciality"
			},
			phone: {
				required: "Please enter a phone"
			},
			email: {
				required: "Please enter a email"
			},
			home_address: {
				required: "Please enter a home address"
			},
			present_address: {
				required: "Please enter a present address"
			},
			counselor: {
				required: "Please enter a counselor name"
			},
			counselor_phone: {
				required: "Please enter a counselor phone"
			},
			counselor_email: {
				required: "Please enter a counselor email"
			},
			avatar: {
				required: "Please select a photo"
			},
			wakeup_time: {
				required: "Please enter a wake-up time"
			},
			bedtime: {
				required: "Please enter a bedtime"
			},
			smoking_status: {
				required: "Please select smoking status"
			},
			frequency_of_cleaning: {
				required: "Please enter frequency of cleaning"
			},
			hobby: {
				required: "Please enter hobby"
			},
			reason: {
				required: "Please enter reason"
			}
		},
		errorElement: "em",
		errorPlacement: function ( error, element ) {
			// Add the `help-block` class to the error element
			error.addClass( "help-block" );

			// Add `has-feedback` class to the parent div.form-group
			// in order to add icons to inputs
			element.parents( ".col-md-6" ).addClass( "has-feedback" );

			if ( element.prop( "type" ) === "checkbox" ) {
				error.insertAfter( element.parent( "label" ) );
			} else {
				error.insertAfter( element );
			}
			if ( element.prop( "type" ) === "radio" ) {
				error.insertAfter( element.parent( "label" ) );
			} else {
				error.insertAfter( element );
			}
			// Add the span element, if doesn't exists, and apply the icon classes to it.
		},
		highlight: function ( element, errorClass, validClass ) {
			$( element ).parents( ".col-md-6" ).addClass( "has-error" ).removeClass( "has-success" );
			$( element ).parents( ".col-md-12" ).addClass( "has-error" ).removeClass( "has-success" );
			//$( element ).next( "span" ).addClass( "glyphicon-remove" ).removeClass( "glyphicon-ok" );
		},
		unhighlight: function ( element, errorClass, validClass ) {
			$( element ).parents( ".col-md-6" ).addClass( "has-success" ).removeClass( "has-error" );
			$( element ).parents( ".col-md-12" ).addClass( "has-success" ).removeClass( "has-error" );
			//$( element ).next( "span" ).addClass( "glyphicon-ok" ).removeClass( "glyphicon-remove" );
		}
	} );
	$( "#renewuploadForm" ).validate( {
		rules: {
			name: {
				required: true
			},
			birth_of_date: {
				required: true
			},
			gender: {
				required: true
			},
			native_place: {
				required: true
			},
			grade: {
				required: true
			},
			speciality: {
				required: true
			},
			
			phone: {
				required: true
			},
			email: {
				required: true
			},
			home_address: {
				required: true
			},
			present_address: {
				required: true
			},
			counselor: {
				required: true
			},
			counselor_phone: {
				required: true
			},
			counselor_email: {
				required: true
			},
			avatar: {
				required: true
			},
			wakeup_time: {
				required: true
			},
			bedtime: {
				required: true
			},
			
			hobby: {
				required: true
			},
			reason: {
				required: true
			}
		},
		messages: {
			name: {
				required: "Please enter a name"
			},
			birth_of_date: {
				required: "Please enter a birth of date"
			},
			gender: {
				required: "Please select a gender"
			},
			native_place: {
				required: "Please enter a native place"
			},
			grade: {
				required: "Please enter a grade"
			},
			speciality: {
				required: "Please enter a speciality"
			},
			phone: {
				required: "Please enter a phone"
			},
			email: {
				required: "Please enter a email"
			},
			home_address: {
				required: "Please enter a home address"
			},
			present_address: {
				required: "Please enter a present address"
			},
			counselor: {
				required: "Please enter a counselor name"
			},
			counselor_phone: {
				required: "Please enter a counselor phone"
			},
			counselor_email: {
				required: "Please enter a counselor email"
			},
			avatar: {
				required: "Please select a photo"
			},
			wakeup_time: {
				required: "Please enter a wake-up time"
			},
			bedtime: {
				required: "Please enter a bedtime"
			},
			hobby: {
				required: "Please enter hobby"
			},
			reason: {
				required: "Please enter reason"
			}
		},
		errorElement: "em",
		errorPlacement: function ( error, element ) {
			// Add the `help-block` class to the error element
			error.addClass( "help-block" );

			// Add `has-feedback` class to the parent div.form-group
			// in order to add icons to inputs
			element.parents( ".col-md-6" ).addClass( "has-feedback" );

			if ( element.prop( "type" ) === "checkbox" ) {
				error.insertAfter( element.parent( "label" ) );
			} else {
				error.insertAfter( element );
			}
			if ( element.prop( "type" ) === "radio" ) {
				error.insertAfter( element.parent( "label" ) );
			} else {
				error.insertAfter( element );
			}
			// Add the span element, if doesn't exists, and apply the icon classes to it.
		},
		highlight: function ( element, errorClass, validClass ) {
			$( element ).parents( ".col-md-6" ).addClass( "has-error" ).removeClass( "has-success" );
			$( element ).parents( ".col-md-12" ).addClass( "has-error" ).removeClass( "has-success" );
			//$( element ).next( "span" ).addClass( "glyphicon-remove" ).removeClass( "glyphicon-ok" );
		},
		unhighlight: function ( element, errorClass, validClass ) {
			$( element ).parents( ".col-md-6" ).addClass( "has-success" ).removeClass( "has-error" );
			$( element ).parents( ".col-md-12" ).addClass( "has-success" ).removeClass( "has-error" );
			//$( element ).next( "span" ).addClass( "glyphicon-ok" ).removeClass( "glyphicon-remove" );
		}
	} );
	$( "#teacheruploadForm" ).validate( {
		rules: {
			name: {
				required: true
			},
			birth_of_date: {
				required: true
			},
			gender: {
				required: true
			},
			native_place: {
				required: true
			},
			education: {
				required: true
			},
			speciality: {
				required: true
			},
			
			phone: {
				required: true
			},
			email: {
				required: true
			},
			school: {
				required: true
			},
			present_address: {
				required: true
			},
			experience: {
				required: true
			},
			avatar: {
				required: true
			}
		},
		messages: {
			name: {
				required: "Please enter a name"
			},
			birth_of_date: {
				required: "Please enter a birth of date"
			},
			gender: {
				required: "Please select a gender"
			},
			native_place: {
				required: "Please enter a native place"
			},
			education: {
				required: "Please select a education"
			},
			speciality: {
				required: "Please enter a speciality"
			},
			phone: {
				required: "Please enter a phone"
			},
			email: {
				required: "Please enter a email"
			},
			school: {
				required: "Please enter a school"
			},
			present_address: {
				required: "Please enter a present address"
			},
			experience: {
				required: "Please enter a experience"
			},
			avatar: {
				required: "Please select a photo"
			}
		},
		errorElement: "em",
		errorPlacement: function ( error, element ) {
			// Add the `help-block` class to the error element
			error.addClass( "help-block" );

			// Add `has-feedback` class to the parent div.form-group
			// in order to add icons to inputs
			element.parents( ".col-md-6" ).addClass( "has-feedback" );

			if ( element.prop( "type" ) === "checkbox" ) {
				error.insertAfter( element.parent( "label" ) );
			} else {
				error.insertAfter( element );
			}
			if ( element.prop( "type" ) === "radio" ) {
				error.insertAfter( element.parent( "label" ) );
			} else {
				error.insertAfter( element );
			}
			// Add the span element, if doesn't exists, and apply the icon classes to it.
		},
		highlight: function ( element, errorClass, validClass ) {
			$( element ).parents( ".col-md-6" ).addClass( "has-error" ).removeClass( "has-success" );
			$( element ).parents( ".col-md-12" ).addClass( "has-error" ).removeClass( "has-success" );
			//$( element ).next( "span" ).addClass( "glyphicon-remove" ).removeClass( "glyphicon-ok" );
		},
		unhighlight: function ( element, errorClass, validClass ) {
			$( element ).parents( ".col-md-6" ).addClass( "has-success" ).removeClass( "has-error" );
			$( element ).parents( ".col-md-12" ).addClass( "has-success" ).removeClass( "has-error" );
			//$( element ).next( "span" ).addClass( "glyphicon-ok" ).removeClass( "glyphicon-remove" );
		}
	} );
	$( "#appointmentForm" ).validate( {
		ignore: "",
		rules: {
			phone: {
				required: true,
			},
			interviewdate_id: {
				required: true,
			},
			interviewtime_id: {
				required: true
			}
		},
		messages: {
			phone: {
				required: "Please enter a phone",
			},
			interviewdate_id: {
				required: "Please select a interview date",
			},
			interviewtime_id: {
				required: "Please select a interview time",
			}
		},
		errorElement: "em",
		errorPlacement: function ( error, element ) {
			// Add the `help-block` class to the error element
			error.addClass( "help-block" );

			// Add `has-feedback` class to the parent div.form-group
			// in order to add icons to inputs
			element.parents( ".col-sm-6" ).addClass( "has-feedback" );

			if ( element.prop( "type" ) === "checkbox" ) {
				error.insertAfter( element.parent( "label" ) );
			} else {
				error.insertAfter( element );
			}

			// Add the span element, if doesn't exists, and apply the icon classes to it.
		},
		highlight: function ( element, errorClass, validClass ) {
			$( element ).parents( ".col-sm-6" ).addClass( "has-error" ).removeClass( "has-success" );
			$( element ).next( "span" ).addClass( "glyphicon-remove" ).removeClass( "glyphicon-ok" );
		},
		unhighlight: function ( element, errorClass, validClass ) {
			$( element ).parents( ".col-sm-6" ).addClass( "has-success" ).removeClass( "has-error" );
			$( element ).next( "span" ).addClass( "glyphicon-ok" ).removeClass( "glyphicon-remove" );
		}
	} );
} );