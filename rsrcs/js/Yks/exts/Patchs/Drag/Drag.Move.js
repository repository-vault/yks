Drag.Move.implement({
    stop: function(event){
        this.checkDroppables();
        this.fireEvent('drop', [this.element, this.overed, event]);

        if(this.overed && this.overed.fireEvent)
            this.overed.fireEvent('dropinto', [this.element, event]);

        this.overed = null;
        return this.parent(event);
    }
});
