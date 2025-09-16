    </div>
    
    <script>
        // Copy to clipboard functionality
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show feedback
                const btn = event.target;
                const originalText = btn.textContent;
                btn.textContent = 'Copied!';
                setTimeout(() => {
                    btn.textContent = originalText;
                }, 2000);
            }).catch(function() {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                
                const btn = event.target;
                const originalText = btn.textContent;
                btn.textContent = 'Copied!';
                setTimeout(() => {
                    btn.textContent = originalText;
                }, 2000);
            });
        }
        
        // Auto-refresh functionality
        function enableAutoRefresh(intervalSeconds = 30) {
            if (intervalSeconds > 0) {
                setTimeout(() => {
                    window.location.reload();
                }, intervalSeconds * 1000);
            }
        }
        
        // Confirmation dialogs
        function confirmDelete(itemName) {
            return confirm(`Are you sure you want to delete "${itemName}"? This action cannot be undone.`);
        }
        
        // Form validation helpers
        function validateUrl(input) {
            const url = input.value.trim();
            if (url && !url.match(/^https?:\/\/.+/)) {
                input.setCustomValidity('Please enter a valid URL starting with http:// or https://');
            } else {
                input.setCustomValidity('');
            }
        }
        
        // Initialize tooltips and other interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // Add click handlers for copy buttons
            document.querySelectorAll('.copy-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const text = this.getAttribute('data-copy') || this.previousElementSibling.textContent;
                    copyToClipboard(text);
                });
            });
            
            // Add URL validation to URL inputs
            document.querySelectorAll('input[type="url"]').forEach(input => {
                input.addEventListener('input', function() {
                    validateUrl(this);
                });
            });
        });
    </script>
</body>
</html>