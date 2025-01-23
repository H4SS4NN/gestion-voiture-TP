document.addEventListener("DOMContentLoaded", function () {
    // Récupérer les plages de dates indisponibles depuis Twig
    const unavailableDates = JSON.parse(
        document.getElementById("unavailable-dates").dataset.unavailable
    );

    // Configurer Flatpickr pour la date de début
    flatpickr(".flatpickr-start", {
        dateFormat: "Y-m-d",
        minDate: "today", // La date minimale est aujourd'hui
        disable: unavailableDates.map((range) => ({
            from: range.start,
            to: range.end,
        })),
        onChange: function (selectedDates) {
            const startDate = selectedDates[0];
            if (startDate) {
                // Mettre à jour la date minimale de la date de fin
                const endDatePicker = flatpickr(".flatpickr-end", {});
                endDatePicker.set("minDate", startDate);
            }
        },
    });

    // Configurer Flatpickr pour la date de fin
    flatpickr(".flatpickr-end", {
        dateFormat: "Y-m-d",
        minDate: "today", // Par défaut, aujourd'hui
        disable: unavailableDates.map((range) => ({
            from: range.start,
            to: range.end,
        })),
    });
});
