'use strict';

Vue.component('ratings', {
	template: '#template-ratings',
	data: function data() {
		return {
			value: null,
			temp_value: null,
			ratings: [1, 2, 3, 4, 5],
			disabled: true,
			voted: false
		};
	},
	props: {
		name: String,
		value: null,
		id: String,
		required: Boolean
	},
	methods: {
		starOver: function star_over(index) {
			if (this.disabled) {
				return;
			}
			this.temp_value = this.value;
			this.value = index;
		},
		starOut: function star_out() {
			if (this.disabled) {
				return;
			}

			this.value = this.temp_value;
		},
		set: function set(value) {
			if (this.disabled) {
				return;
			}

			this.temp_value = value;
			this.value = value;
			// Spremi
			this.saveRating(value);
			// Isključi
			this.disabled = true;
			// Pokaži poruku
			this.voted = true;
		},
		// Save voted rating into database
		saveRating(rating) {
			jQuery.ajax({
				data: {
					action: 'save_rating',
					nonce: postratings.nonce,
					post_id: postratings.post_id,
					rating: rating
				},
				type: 'POST',
				url: postratings.url,
				success: function(data) {
					//console.log(data);
				},
				error: function(data) {
					console.log(data);
				}
			});
		},
		// Check session if user can vote
		checkCanVote: function() {
			var self = this;
			jQuery.ajax({
				data: {
					action: 'ratings_check_session',
					nonce: postratings.nonce,
					post_id: postratings.post_id
				},
				type: 'POST',
				url: postratings.url,
				success: function(data) {
					//console.log(data);
					// User has already voted
					if (data.can_vote == false) self.disabled = true;
					else self.disabled = false;
				},
				error: function(error) {
					console.log(error);
				}
			});
		}
	},
	mounted() {
		this.checkCanVote();
	}
});

new Vue({
	el: '#star-app'
});
