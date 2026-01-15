</main>
<footer style="margin-top: 50px; text-align: center; color: var(--text-muted); padding: 20px; font-size: 0.9rem;">
    &copy;
    <?php echo date("Y"); ?> LevelUp English AI Learning Systems.
</footer>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const dropdown = document.querySelector(".dropdown");
  const toggle = document.querySelector(".dropdown-toggle");

  if (!dropdown || !toggle) return;

  toggle.addEventListener("click", function (e) {
    e.preventDefault();
    e.stopPropagation();
    dropdown.classList.toggle("open");
  });

  document.addEventListener("click", function () {
    dropdown.classList.remove("open");
  });
});
</script>
</body>

</html>