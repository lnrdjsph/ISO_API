document.addEventListener("DOMContentLoaded", function () {
    const controllers = document.querySelectorAll(".dispatch-controller");

    controllers.forEach((controller) => {
        // Initial state
        handleToggle(controller);

        // Listen for changes
        controller.addEventListener("change", () => handleToggle(controller));
    });

    function handleToggle(selectElement) {
        const hideValue = selectElement.getAttribute("data-hide-value");
        const targetSelector = selectElement.getAttribute("data-target");
        const targetGroup = document.querySelector(targetSelector);

        if (!targetGroup) return;

        if (!selectElement.value || selectElement.value === hideValue) {
            // Hide with animation
            targetGroup.classList.add("opacity-0", "max-h-0");
            targetGroup.classList.remove(
                "opacity-100",
                "max-h-[1000px]",
                "animate-surpriseBounce"
            );

            // Delay full hide
            setTimeout(() => {
                targetGroup.classList.add("hidden");
            }, 500);
        } else {
            // Show immediately
            targetGroup.classList.remove("hidden");

            // Animate visible
            setTimeout(() => {
                targetGroup.classList.remove("opacity-0", "max-h-0");
                targetGroup.classList.add(
                    "opacity-100",
                    "max-h-[1000px]",
                    "animate-surpriseBounce"
                );
            }, 10);
        }
    }
});
