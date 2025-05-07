jQuery(document).ready(function($) {
    // Get the Facebook posts container
    const postsContainer = $('#bishops-facebook-posts');
    
    // If container exists on the page
    if (postsContainer.length) {
        // Make AJAX request to get the Facebook posts
        $.ajax({
            url: bishopsFBSettings.ajaxurl,
            type: 'GET',
            data: {
                action: 'get_facebook_posts',
                nonce: bishopsFBSettings.nonce
            },
            success: function(response) {
                if (response.success && response.data && response.data.data) {
                    displayPosts(response.data.data);
                } else {
                    showError('Failed to load posts. Please try again later.');
                }
            },
            error: function() {
                showError('Error connecting to the server. Please try again later.');
            }
        });
    }
    
    // Display the posts
    function displayPosts(posts) {
        // Clear loading indicator
        postsContainer.empty();
        
        // Check if we have posts
        if (!posts || posts.length === 0) {
            postsContainer.html('<p>No posts available at this time.</p>');
            return;
        }
        
        // Loop through each post
        posts.forEach(function(post) {
            // Format date
            const postDate = new Date(post.created_time);
            const formattedDate = formatDate(postDate);
            
            // Get the post message or a default message
            const message = post.message || 'Visit our Facebook page for more information.';
            
            // Get media content (photo or video)
            let mediaContent = '';
            if (post.attachments && post.attachments.data && post.attachments.data.length > 0) {
                const attachment = post.attachments.data[0];
                
                if (attachment.media_type === 'video' && attachment.media && attachment.media.image) {
                    // Video with thumbnail
                    mediaContent = `
                        <div class="bishops-post-video">
                            <img src="${attachment.media.image.src}" alt="Video thumbnail">
                            <div class="bishops-play-button">
                                <div class="bishops-play-icon"></div>
                            </div>
                        </div>
                    `;
                } else if (attachment.media_type === 'photo' && attachment.media && attachment.media.image) {
                    // Photo
                    mediaContent = `<img class="bishops-post-image" src="${attachment.media.image.src}" alt="Post image">`;
                }
            }
            
            // Get like and comment counts
            const likeCount = post.likes && post.likes.summary ? post.likes.summary.total_count : 0;
            const commentCount = post.comments && post.comments.summary ? post.comments.summary.total_count : 0;
            
            // Create post HTML
            const postHTML = `
                <div class="bishops-post-card">
                    <div class="bishops-post-header">
                        <img class="bishops-post-logo" src="https://graph.facebook.com/${post.id.split('_')[0]}/picture" alt="Page Logo">
                        <div class="bishops-post-org">Malawi Conference of Catholic Bishops</div>
                        <div class="bishops-post-date">${formattedDate}</div>
                    </div>
                    <div class="bishops-post-content">
                        <p>${message}</p>
                        ${mediaContent}
                    </div>
                    <div class="bishops-post-stats">
                        <div class="bishops-post-likes">${likeCount}</div>
                        <div class="bishops-post-comments">${commentCount}</div>
                    </div>
                </div>
            `;
            
            // Append to container
            postsContainer.append(postHTML);
        });
        
        // Add click event for videos to open Facebook post
        $('.bishops-post-video').on('click', function() {
            const postCard = $(this).closest('.bishops-post-card');
            const postIndex = postCard.index();
            
            if (posts[postIndex] && posts[postIndex].id) {
                const postUrl = `https://www.facebook.com/${posts[postIndex].id}`;
                window.open(postUrl, '_blank');
            }
        });
    }
    
    // Show error message
    function showError(message) {
        postsContainer.html(`<p class="bishops-feed-error">${message}</p>`);
    }
    
    // Format date: "February 25, 2025"
    function formatDate(date) {
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    }
});
