<script setup>
import { ref, watch, computed, h } from "vue";
import { Head, router } from "@inertiajs/vue3";
import {
    NDataTable,
    NPagination,
    NInput,
    NButton,
    NIcon,
    NSpace,
    NCheckbox,
} from "naive-ui";
import { CheckmarkDoneOutline, CloseOutline } from "@vicons/ionicons5";
import { usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { reactive } from "vue";
import TodoModal from "@/Components/TodoModal.vue";
import { useMessage, useDialog } from "naive-ui";

defineOptions({ layout: AuthenticatedLayout });

const message = useMessage();
const dialog = useDialog();

// Modal state
const modal = reactive({
    show: false,
    todo: null, // null = create mode, object = edit mode
});

function openCreateModal() {
    modal.todo = null;
    modal.show = true;
}

function openEditModal(todo) {
    modal.todo = { ...todo };
    modal.show = true;
}

function closeModal() {
    modal.show = false;
    modal.todo = null;
}

function deleteTodo(todo) {
    const params = new URLSearchParams();
    if (props.todos.current_page && props.todos.current_page !== 1) {
        params.set("page", props.todos.current_page);
    }
    if (props.filters.search) {
        params.set("search", props.filters.search);
    }
    const queryString = params.toString();
    const url = `/todos/${todo.id}` + (queryString ? `?${queryString}` : "");

    dialog.warning({
        title: "Delete Todo",
        content: `Are you sure you want to delete "${todo.text}"?`,
        positiveText: "Delete",
        negativeText: "Cancel",
        onPositiveClick: () => {
            router.delete(url, {
                preserveScroll: true,
                onSuccess: () => message.success("Todo deleted."),
                onError: () => message.error("Failed to delete todo."),
            });
        },
    });
}

// Props from Laravel
const props = defineProps({
    todos: Object, // Laravel paginator
    filters: Object, // { search: '' }
});

// Current user from Inertia shared props (set by Breeze)
const authUser = computed(() => usePage().props.auth.user);

// Local search state, initialised from the URL filter
const search = ref(props.filters.search || "");

// Debounced search: wait 300ms after typing stops, then send an Inertia GET request
let timer = null;
watch(search, (value) => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get(
            "/todos",
            { search: value },
            { preserveState: true, replace: true },
        );
    }, 300);
});

// Handle page change from Naive UI pagination
function handlePageChange(page) {
    router.get(
        "/todos",
        { page, search: search.value },
        { preserveState: true },
    );
}

// Table columns configuration
const columns = computed(() => {
    const cols = [
        {
            title: "Text",
            key: "text",
            sorter: false, // we’ll stick with server-side
        },
        {
            title: "Completed",
            key: "is_completed",
            width: 120,
            align: "center",
            render(row) {
                return row.is_completed
                    ? h(
                          NIcon,
                          { color: "green", size: 18 },
                          { default: () => h(CheckmarkDoneOutline) },
                      )
                    : h(
                          NIcon,
                          { color: "gray", size: 18 },
                          { default: () => h(CloseOutline) },
                      );
            },
        },
        {
            title: "Created",
            key: "created_at",
            width: 180,
            render(row) {
                return new Date(row.created_at).toLocaleDateString();
            },
        },
    ];

    // Admin sees an extra column for user name
    if (authUser.value?.role === "admin") {
        cols.push({
            title: "User",
            key: "user.name",
            width: 150,
        });
    }

    // Actions column (will be filled later)
    cols.push({
        title: "Actions",
        key: "actions",
        width: 150,
        align: "center",
        render(row) {
            return h(
                NSpace,
                { justify: "center" },
                {
                    default: () => [
                        h(
                            NButton,
                            {
                                size: "small",
                                quaternary: true,
                                onClick: () => openEditModal(row), // ← adds click
                            },
                            { default: () => "Edit" },
                        ),
                        h(
                            NButton,
                            {
                                size: "small",
                                quaternary: true,
                                type: "error",
                                onClick: () => deleteTodo(row), // ← adds click
                            },
                            { default: () => "Delete" },
                        ),
                    ],
                },
            );
        },
    });

    return cols;
});

watch(
    () => props.filters.search,
    (newSearch) => {
        search.value = newSearch || "";
    },
);
</script>

<template>
    <Head title="Dashboard" />

    <!-- <AuthenticatedLayout> -->
    <div class="p-8">
        <h1 class="text-2xl font-bold mb-4">Todos</h1>

        <!-- Search bar -->
        <div class="mb-4 flex gap-2 items-center">
            <NInput
                v-model:value="search"
                placeholder="Search todos..."
                clearable
                style="max-width: 300px"
            />
            <NButton type="primary" @click="openCreateModal">
                New Todo
            </NButton>
        </div>

        <!-- Naive UI Data Table -->
        <NDataTable
            :columns="columns"
            :data="todos.data"
            :row-key="(row) => row.id"
            :bordered="true"
            :single-line="false"
        />

        <!-- Server-side Pagination -->
        <div class="mt-4 flex justify-end">
            <NPagination
                :page="todos.current_page"
                :page-size="todos.per_page"
                :item-count="todos.total"
                @update:page="handlePageChange"
            />
        </div>
        <TodoModal
            :show="modal.show"
            :todo="modal.todo"
            :current-page="todos.current_page"
            :filters="filters"
            @close="closeModal"
        />
    </div>
    <!-- </AuthenticatedLayout> -->
</template>
