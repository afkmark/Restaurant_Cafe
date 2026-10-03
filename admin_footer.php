    </main>
    </section>

    <script>
        const menuBar = document.querySelector("#content nav .bx.bx-menu"),
            sidebar = document.getElementById("sidebar");

        menuBar.addEventListener("click", () => {
            sidebar.classList.toggle("hide");
        });

        document.getElementById("switch-mode").addEventListener("change", e => {
            document.body.classList.toggle("dark", e.target.checked);
        });
    </script>
    </body>

    </html>