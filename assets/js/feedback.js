var vm = new Vue({
	el: '#js-userfeedback',

	data: {
		// User has a right to vote
		canVote: false,
		// User has clicked to initiate a vote (positive or negative)
		wantsToVote: false,
		// User has clicked negatively
		negativeReaction: false,
		// Email with feedback has been sent
		messageSent: false,
		feedbackMessage: ''
	},

	mounted() {
		this.check_session();
	},

	methods: {
		// Check if person has already voted
		check_session: function() {
			var self = this;
			jQuery.ajax({
				data: {
					action: 'check_session',
					page_id: userfeedback.page_id,
					nonce: userfeedback.nonce
				},
				type: 'POST',
				url: userfeedback.url,
				success: function(data) {
					//console.log(data);
					if (data == 1) {
						self.canVote = true;
					}
				},
				error: function(error) {
					console.log(error);
				}
			});
		},

		// Save feedback
		vote: function(reaction) {
			if (reaction === 'negative_feedback') {
				this.negativeReaction = true;
			}
			if (!this.canVote) {
				this.wantsToVote = true;
				return;
			}

			var self = this;
			jQuery.ajax({
				data: {
					action: 'vote',
					nonce: userfeedback.nonce,
					page_id: userfeedback.page_id,
					meta_key: reaction
				},
				type: 'POST',
				url: userfeedback.url,
				success: function(data) {
					//console.log(data);
					if (data.vote_passed === true) self.wantsToVote = true;
				},
				error: function(error) {
					console.log(error);
				}
			});
		},

		// Send email
		// Nevermind if maybe there was an error, just show appreciation to the user
		sendMessage: function(e) {
			e.preventDefault();
			var self = this;

			jQuery.ajax({
				data: {
					action: 'send_message',
					nonce: userfeedback.nonce,
					message: this.feedbackMessage,
					title: userfeedback.page_title,
					url: userfeedback.page_url
				},
				type: 'POST',
				url: userfeedback.url,
				dataType: 'json',
				success: function(data) {
					//console.log(data);
					self.messageSent = true;
					this.feedbackMessage = '';
				},
				error: function(error) {
					console.log(error);
					self.messageSent = true;
					this.feedbackMessage = '';
				}
			});
		}
	}
});
