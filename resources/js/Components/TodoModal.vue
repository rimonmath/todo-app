<script setup>
import { ref, computed, watch } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import {
    NModal,
    NForm,
    NFormItem,
    NInput,
    NCheckbox,
    NButton,
    NSpace,
    useMessage,
} from "naive-ui";

const props = defineProps({
    show: Boolean, // controls visibility
    todo: Object, // null for create, existing todo object for edit
});

const emit = defineEmits(["close"]);

const message = useMessage();

// Form fields
const form = ref({
    text: "",
    is_completed: false,
});

// When the todo prop changes (edit mode), fill the form
watch(
    () => props.todo,
    (newTodo) => {
        if (newTodo) {
            form.value = {
                text: newTodo.text || "",
                is_completed: !!newTodo.is_completed,
            };
        } else {
            form.value = { text: "", is_completed: false };
        }
    },
    { immediate: true },
);

// Dialog title
const title = computed(() => (props.todo ? "Edit Todo" : "New Todo"));

function close() {
    emit("close");
}

function submit() {
    if (!form.value.text.trim()) {
        message.warning("Text is required.");
        return;
    }

    const method = props.todo ? "put" : "post";
    const url = props.todo ? `/todos/${props.todo.id}` : "/todos";

    const inertiaForm = useForm(form.value);

    inertiaForm.submit(method, url, {
        onSuccess: () => {
            message.success(props.todo ? "Todo updated." : "Todo created.");
            close();
        },
        onError: (errors) => {
            if (errors.text) message.error(errors.text);
            else message.error("Something went wrong.");
        },
        preserveScroll: true,
    });
}
</script>

<template>
    <NModal :show="show" @update:show="close">
        <div class="bg-white rounded-lg p-6 w-full max-w-lg mx-auto">
            <h2 class="text-lg font-semibold mb-4">{{ title }}</h2>

            <NForm label-placement="top">
                <NFormItem label="Todo text" required>
                    <NInput
                        v-model:value="form.text"
                        placeholder="What needs to be done?"
                        autofocus
                    />
                </NFormItem>

                <NFormItem label="Completed">
                    <NCheckbox v-model:checked="form.is_completed">
                        Mark as completed
                    </NCheckbox>
                </NFormItem>

                <NSpace justify="end">
                    <NButton @click="close">Cancel</NButton>
                    <NButton type="primary" @click="submit">
                        {{ props.todo ? "Update" : "Create" }}
                    </NButton>
                </NSpace>
            </NForm>
        </div>
    </NModal>
</template>
