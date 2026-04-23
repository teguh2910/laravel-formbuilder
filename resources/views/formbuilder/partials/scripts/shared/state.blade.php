        const apiBase = "/formbuilder/api";
        const csrfToken = "{{ csrf_token() }}";
        const authStorageKey = "formbuilder_auth_user";

        let users = [];
        let depts = [];
        const fieldTypes = [
            { value: "text", label: "Text Input", icon: "Aa" },
            { value: "textarea", label: "Text Area", icon: "Txt" },
            { value: "number", label: "Number", icon: "#" },
            { value: "email", label: "Email", icon: "@" },
            { value: "date", label: "Date", icon: "Dt" },
            { value: "dropdown", label: "Dropdown", icon: "v" },
            { value: "radio", label: "Radio", icon: "o" },
            { value: "checkbox", label: "Checkbox", icon: "[x]" },
            { value: "file", label: "File Upload", icon: "Fl" },
            { value: "calculation", label: "Calculation", icon: "Sum" },
            { value: "table", label: "Table", icon: "Tbl" },
        ];

        let templates = [];
        let submissions = [];

        const views = {
            landing: document.getElementById("view-landing"),
            login: document.getElementById("view-login"),
            fillList: document.getElementById("view-fill-list"),
            fillForm: document.getElementById("view-fill-form"),
            track: document.getElementById("view-track"),
            admin: document.getElementById("view-admin"),
            mySubmissions: document.getElementById("view-my-submissions"),
        };

        const toastEl = document.getElementById("toast");
        let selectedTemplate = null;
        let formData = {};
        let currentUser = null;
        let adminPage = "dashboard";
        let editorDraft = null;
        let editorTab = "fields";
