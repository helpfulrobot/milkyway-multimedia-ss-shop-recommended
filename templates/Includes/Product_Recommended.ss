<% if $Recommended %>
    <div class="product-recommended">
        <h4 class="product-recommended-title">$RecommendedTitle</h4>
        <ul class="product-recommended-list">
            <% loop $Recommended %>
                <li class="product-recommended-listing">
                    <% include ProductGroupItem %>
                </li>
            <% end_loop %>
        </ul>
    </div>
<% end_if %>